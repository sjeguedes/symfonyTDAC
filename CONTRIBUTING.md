# Contributing to this application

## Welcome!
* [Report a bug](https://github.com/sjeguedes/symfonyTDAC/issues/new?labels=type/bug)
* [Propose a new feature](https://github.com/sjeguedes/symfonyTDAC/issues/new?labels=type/enhancement)
* [Send a pull request](https://github.com/sjeguedes/symfonyTDAC/pulls)

## We have a code of conduct to respect some rules
Please note that this project is released with a [Contributor code of conduct](CODE_OF_CONDUCT.md). By participating in this project you agree to abide by its terms.

## Write bug reports with detail, background, and sample code
In your bug report, please provide the following:

* A quick summary and/or background
*Provide a summary describing the problem you are experiencing.*
* Which action(s) was (were) done to reproduce  
*Provide steps to reproduce the bug.*
  * Be specific!
  * Give sample code if you can.
* What you expected would happen  
*What was the expected (correct) behavior?*
* What actually happens  
*What is the current (buggy) behavior?*
* Notes (possibly including why you think this might be happening, or stuff you tried that didn't work)

- Please keep the table shown below at the top of your issue.

| infos                   | version
| ----------------------- | ---------------
| Application version     | x.y.z
| PHP version             | x.y.z
| ...                     | ...

- Please post code as text (using proper markup). Do not post screenshots of code.
- Look at our support first to possibly find similar report.
- Please use the most specific issue tracker (if it exists) to search for existing tickets and to open new tickets.

## Workflow for Pull Requests

1. Fork the repository.
2. Create your branch from `master` if you plan to implement new functionality or change existing code significantly.   
Create your branch from the oldest branch that is affected by the bug if you plan to fix a bug.
3. Implement your change and add automated tests (prefer [PHPUnit](https://phpunit.readthedocs.io) tests as regards our project) for it.
4. Ensure the test suite passes.
5. Ensure the code complies with our coding guidelines (see below).
6. Send that pull request!

Please make sure you have [set up your user name and email address](https://git-scm.com/book/en/v2/Getting-Started-First-Time-Git-Setup) for use with Git. Strings such as `silly nick name <root@localhost>` look really stupid in the commit history of a project.  
We encourage you to [sign your Git commits with your GPG key](https://docs.github.com/en/github/authenticating-to-github/signing-commits).  
Pull requests for bug fixes must be made for the oldest branch that is supported.   
Pull requests for new features must be based on the `master` branch.  
We are trying to keep backwards compatibility breaks to an absolute minimum.   
Please take this into account when proposing changes.  
Due to time constraints, we are not always able to respond as quickly as we would like. Please do not take delays personal and feel free to remind us if you feel that we forgot to respond!  

## Coding Guidelines to follow
Install dependencies using [Composer](https://getcomposer.org/).  
- We first recommend to use [phpstan](https://phpstan.org) tools to perform static analysis:

```bash
$ php composer require --dev phpstan/phpstan
# Analyse "src" and "tests" directories
$ php vendor/bin/phpstan analyse src tests
```

- We also encourage you to use [php_codesniffer](https://github.com/squizlabs/PHP_CodeSniffer) to (re)format your source code for compliance with this project's coding (standards and style) guidelines:

```bash
$ php composer require "squizlabs/php_codesniffer=*" --dev
# Analyse code
$ php ./vendor/bin/phpcs
# fix issues
$ php ./vendor/bin/phpcbf
```
Please understand that we will not accept a pull request when its changes violate this project's coding guidelines.

## Running your own PHPUnit test suite

After following the steps shown above, always run your automated tests:

```bash
$ php bin/phpunit
```
You can integrate code guidelines and automated tests checks in continuous integration process such as [GitHub actions](https://github.com/features/actions) workflows or use other existing tools.  
We thank you in advance for your efforts.

N.B: please note that our contribution guidelines are mostly inspired from professional GitHub repository rules.