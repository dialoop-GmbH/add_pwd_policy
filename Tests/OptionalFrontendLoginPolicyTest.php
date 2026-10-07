<?php
declare(strict_types=1);
use Composer\Autoload\ClassLoader;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\PasswordPolicy\Event\EnrichPasswordValidationContextDataEvent;
use TYPO3\CMS\Core\PasswordPolicy\Validator\Dto\ContextData;
use TYPO3\CMS\Core\PasswordPolicy\PasswordPolicy;
use TYPO3\CMS\Core\PasswordPolicy\PasswordPolicyAction;
$project = realpath($argv[1] ?? '');
$baselinePath = realpath($argv[2] ?? '');
if (!$project || !$baselinePath) throw new RuntimeException('Pass the TYPO3 project and baseline package paths.');
chdir($project);
$loader = require $project . '/vendor/autoload.php';
foreach (spl_autoload_functions() as $autoload) spl_autoload_unregister($autoload);
$isolated = new ClassLoader();
foreach ($loader->getPrefixesPsr4() as $prefix => $paths) {
    if (!str_starts_with($prefix, 'TYPO3\\CMS\\FrontendLogin\\')) $isolated->setPsr4($prefix, $paths);
}
$isolated->addClassMap(array_filter($loader->getClassMap(), static fn(string $class): bool => !str_starts_with($class, 'TYPO3\\CMS\\FrontendLogin\\'), ARRAY_FILTER_USE_KEY));
$isolated->setPsr4('Derhansen\\AddPwdPolicy\\', dirname(__DIR__) . '/Classes');
$isolated->register(true);
if (class_exists('TYPO3\\CMS\\FrontendLogin\\Controller\\PasswordRecoveryController')) throw new RuntimeException('FELogin must be absent');
SystemEnvironmentBuilder::run(1, SystemEnvironmentBuilder::REQUESTTYPE_BE | SystemEnvironmentBuilder::REQUESTTYPE_CLI);
Bootstrap::init($isolated);
$GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('en');
$baseline = require $baselinePath . '/Configuration/Baseline/V14/system.php';
$validatorConfig = $baseline['SYS']['passwordPolicies']['default']['validators'];
$policy = new PasswordPolicy($validatorConfig, PasswordPolicyAction::NEW_USER_PASSWORD);
if (count($policy->getValidators()) !== 4) throw new RuntimeException('Baseline validators missing');
foreach ($validatorConfig as $class => $settings) {
    if (!class_exists($class)) throw new RuntimeException('Validator cannot load: ' . $class);
}
$denylist = GeneralUtility::getFileAbsFileName($validatorConfig[Derhansen\AddPwdPolicy\PasswordPolicy\Validator\PasswordDenylistValidator::class]['options']['passwordDenylistFilepath']);
if (!is_file($denylist) || !is_readable($denylist)) throw new RuntimeException('Denylist is missing');
$context = new ContextData();
(new Derhansen\AddPwdPolicy\EventListener\EnrichContextData())(new EnrichPasswordValidationContextDataEvent($context, ['email'=>'fixture@example.invalid','middle_name'=>'Fixture'], TYPO3\CMS\Core\DataHandling\DataHandler::class));
if ($context->getData('currentEmail') !== 'fixture@example.invalid' || $context->getData('currentMiddleName') !== 'Fixture') throw new RuntimeException('BE context enrichment failed');
if (class_exists('TYPO3\\CMS\\FrontendLogin\\Controller\\PasswordRecoveryController')) throw new RuntimeException('Listener unexpectedly required FELogin');
$context->setData('currentUsername', 'fixtureusername');
foreach ($policy->getValidators() as $validator) {
    if ($validator instanceof Derhansen\AddPwdPolicy\PasswordPolicy\Validator\NotUsernameValidator) {
        if ($validator->validate('prefix-fixtureusername-suffix', $context)) throw new RuntimeException('Username-based password accepted');
        if (!$validator->validate('Unrelated!Fixture987', $context)) throw new RuntimeException('Unrelated password rejected by username validator');
    }
    if ($validator instanceof Derhansen\AddPwdPolicy\PasswordPolicy\Validator\PasswordDenylistValidator) {
        if ($validator->validate('password', $context)) throw new RuntimeException('Denylisted password accepted');
        if (!$validator->validate('Unrelated!Fixture987', $context)) throw new RuntimeException('Unlisted password rejected by denylist validator');
    }
}
echo "All 4 baseline password validators instantiated; denylist readable; BE context enriched and bad passwords rejected with FELogin absent; no writes/network password lookup\n";
