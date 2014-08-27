#Drupal Mail System

[Codeception](http://www.codeception.com) module for testing the [Drupal](http://drupal.org) email system.

```php

// Test to see expected number of emails sent.
$I->seeNumberOfEmailsSent(1);

// Clear emails from queue.
$I->clearSentEmails();

// Check email fields contains text
$I->seeSentEmail(array(
    "body" => "body contains this text",
    "subject" => "subject contains this text",
));
```

Relies on [TestingMailSystem](https://api.drupal.org/api/drupal/modules!system!system.mail.inc/function/TestingMailSystem%3A%3Amail/7) class
which stores the emails in a drupal variable.

#Requirements

* Drupal 7
* DrupalVariable codeception module.

#Install

Install using composer, using git repository (for now).

```
"repositories": [
      {
          "type": "vcs",
          "url": "git@bitbucket.org:dopey/codeception-drupal-mail-system.git"
      },
      {
          "type": "vcs",
          "url": "git@bitbucket.org:dopey/codeception-drupal-variable.git"
      },
],

"require": {
     "ixisandyr/codeception-drupal-mail": "@dev",
     "ixisandyr/codeception-drupal-variable": "@dev",
   },
```
#Configure

Add 'DrupalMailSystem' and 'DrupalVariable' module to the suite configuration.

```
class_name: AcceptanceTester
modules:
    enabled:
        - DrupalMailSystem
        - DrupalVariable
```

##Module configuration

* 'enabled' - set to true to set the TestingMailSystem as default mail system ('mail_system')
at the beginning of the suite run and to restore it at the end. If you set this to false the module
expects you to have set this yourself.
  * `drush vset --format=json 'mail_system' '{"default-system":"TestingMailSystem"}'`