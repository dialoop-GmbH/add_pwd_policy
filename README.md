[![Latest Stable Version](https://poser.pugx.org/derhansen/add_pwd_policy/v/stable)](https://packagist.org/packages/derhansen/sf_event_mgt)
[![Monthly Downloads](https://poser.pugx.org/derhansen/add_pwd_policy/d/monthly)](https://packagist.org/packages/derhansen/add_pwd_policy)
[![Code Quality Checks](https://github.com/derhansen/add_pwd_policy/actions/workflows/CodeQuality.yml/badge.svg)](https://github.com/derhansen/add_pwd_policy/actions/workflows/CodeQuality.yml)
[![Project Status: Active – The project has reached a stable, usable state and is being actively developed.](https://www.repostatus.org/badges/latest/active.svg)](https://www.repostatus.org/#active)

# Additional Password Policy validators for TYPO3 CMS

This extension for TYPO3 CMS contains additional Password Policy validators for
usage in TYPO3 12+ projects. It also adds an event listener for the
`EnrichPasswordValidationContextDataEvent` PSR-14 event, so the context data
used for password validation is extended with the users email-address.

## Included validators

### Pwned Password

#### Description:

This validator ensures that the given password is not part of a known data
breach on haveibeenpwned.com

#### Options:
* none

#### Usage example

```
$GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies']['default']['validators'][\Derhansen\AddPwdPolicy\PasswordPolicy\Validator\PwnedPasswordValidator::class] = [
    'options' => [],
    'excludeActions' => [],
];
```

### Does not contain username

#### Description:

This validator ensures, that the given password does not contain the users `username`.

#### Options:
* none

#### Usage example

```
$GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies']['default']['validators'][\Derhansen\AddPwdPolicy\PasswordPolicy\Validator\NotUsernameValidator::class] = [
    'options' => [],
    'excludeActions' => [],
];
```

### Password deny list

This validator ensures, that the given password is not part of a configurable
list of denied passwords.

The password file must contain one password for each line.

#### Options:
* `passwordDenylistFilepath` Relative path to password file. EXT: notation is allowed.

#### Usage example

```
$GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies']['default']['validators'][\Derhansen\AddPwdPolicy\PasswordPolicy\Validator\PasswordDenylistValidator::class] = [
    'options' => [
        'passwordDenylistFilepath' => 'EXT:add_pwd_policy/Resources/Private/Text/password_denylist.txt',
    ],
    'excludeActions' => [],
];
```

### PRs welcome

If you have created a custom password validator, feel free to provide it as
pull request to this repository.

## Reporting a Vulnerability

Please report vulnerabilities to [security@typo3.org](mailto:security@typo3.org).

## Credits

### Password file

The included file with the top 100.000 popular passwords has been downloaded
from https://github.com/danielmiessler/SecLists

## Dialoop fork: optional frontend login

This fork starts from upstream `derhansen/add_pwd_policy` tag `2.0.1`
(commit `1219998760b153524b4a923d3ef57ed2d68b94d9`). Release `2.0.1.1`
only moves `typo3/cms-felogin` from Composer `require` to `suggest`.
All upstream runtime code, validator settings, password denylist and code-quality
workflow remain unchanged. Backend password policies retain all validators.
Frontend password recovery context integration remains available when FELogin is installed.

The upstream package name remains `derhansen/add_pwd_policy`; consuming projects
select this fork through an explicit VCS repository. No Core package is replaced.

Local compatibility check against the FGS baseline (PHP 8.2 or newer):

```sh
php Tests/OptionalFrontendLoginPolicyTest.php /path/to/typo3-project /path/to/typo3-diabaseline
```

This test excludes FELogin from autoloading in its isolated PHP process, loads this
fork's unchanged PHP classes, checks the four baseline validators and rejects
username-derived and denylisted passwords. It does not modify data or contact the
Pwned Passwords service.
