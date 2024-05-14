---
title: "Setting up Symbiota with Third Party Authentication"
date: 2024-02-19
lastmod: 2024-02-19
icon: "ti-marker-alt"
weight: 2
authors: ["Mark Fisher"]
description: "Learn to configure a Symbiota portal for third party authentication"
type: "docs"
---

# Setting up Symbiota with Third Party Authentication

## This guide contains instructions for users to configure a Symbiota portal to leverage third party authentication platforms.

1. If you have not installed your Symbiota portal as described in the [installation instructions](https://github.com/BioKIC/Symbiota/blob/master/docs/INSTALL.md), complete the installation before proceeding.
2. If you are implementing this on a pre-existing Symbiota portal, you will need to load the new schema from <SymbiotaBaseFolder>/config/schema/3.1/TODO_PATCH_NAME.sql in order to establish the `usersthirdpartyauth` table in the database. Refer to the [installation instructions](https://github.com/BioKIC/Symbiota/blob/master/docs/INSTALL.md) for how to load database schemas.
3. Copy the config/auth_config_template.php file into the same config/ directory and rename it as auth_config.php: `cp config/auth_config_template.php config/auth_config.php`.
4. Obtain a provider URL, client ID, and client secret from your desired third party provider, such as [Microsoft EntraID](https://www.microsoft.com/en-us/security/business/microsoft-entra).
5. Modify the newly-created config/auth_config.php file to include the new values obtained in the previous step.
6. Designate a string value for `$AUTH_PROVIDER` in config/symbini.php. It doesn't matter what the string value is as long as it matches the keys in the associated arrays in config/auth_config.php corresponding to your desired provider. For instance, by default, the value in the symbini_template.php file is `'oid'`, which you will see corresponds to the key `'oid'` in the `$providerUrls`, `$clientIds`, and `$clientSecrets` arrays in config/auth_config.php.
7. Currently, the ability to allow for multiple authentication providers and/or protocols has not been implemented. If you are interested in having this ability on your Symbiota portal and/or are interested in developing it yourself, please [report a new issue](https://github.com/BioKIC/Symbiota/issues/new) about it and coordinate with the current Symbiota development team. When such infrastructure is in place to allow this, the previous three steps can be repeated for as many different providers as desired.
8. You may wish to use a non-OIDC authentication procotol. This is currently not supported, but if so, you will need to create a new login file similar to profile/openIdAuth.php and a callback file similar to /profile/authCallback.php. If you do, you will in turn need to change the value of `$LOGIN_ACTION_PAGE` in symbini.php and the value of `$callBackRedirect` in config/auth_config.php to reflect the paths of the newly-created files.
9. In your config/symbini.php file, make sure that the value of `$THIRD_PARTY_OID_AUTH_ENABLED` is set to `true`.
10. Depending on the needs of your portal, you may want to enable or disable native Symbiota login and/or public user creation (where any user can create their own account). These features can be enabled or disabled by designating the values of `$SYMBIOTA_LOGIN_ENABLED` and `$SHOULD_BE_ABLE_TO_CREATE_PUBLIC_USER`, respectively, in config/symbini.php.
11. Enjoy the new authentication workflow on your portal.

Development and testing were performed using the Microsoft EntraID provider and the OpenID Connect (OIDC) protocol in conjunction with the [Jumbojett\OpenIDConnectClient library](https://github.com/jumbojett/OpenID-Connect-PHP) (note the software requirements listed in this library). If you are using other providers or protocols instead and hit any snags, please [report any issues](https://github.com/BioKIC/Symbiota/issues/new) or even [contribute your improvements to the codebase](https://github.com/BioKIC/Symbiota/blob/master/docs/CONTRIBUTING.md).
