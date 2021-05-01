[![Maintainability](https://api.codeclimate.com/v1/badges/cd0698a66913d668f94b/maintainability)](https://codeclimate.com/github/sjeguedes/symfonyTDAC/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/cd0698a66913d668f94b/test_coverage)](https://codeclimate.com/github/sjeguedes/symfonyTDAC/test_coverage)
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/d7c1a32d50db45d5ab05ec44db19dce0)](https://www.codacy.com/gh/sjeguedes/symfonyTDAC/dashboard?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=sjeguedes/symfonyTDAC&amp;utm_campaign=Badge_Grade)
# SymfonyTDAC

## Symfony upgrade from version 3.1 to 4.4 LTS, with bugs fixing and features improvements
This application manages tasks as a kind of "to do list".  
It is only accessible with a user account, and obviously he has to be authenticated!  

- An anonymous user (visitor) is automatically redirected to login page.
- An authenticated user is redirected to application homepage after successful login action.
- A disconnected user is redirected to login page again.
 
Task features: 
- Each task is associated to a single author.
- Each task is updated by a single last editor.
- A task without author was considered created by an "anonymous author or user".
- A task author, if one exists, cannot be changed after creation.

User features:
- An authenticated user can only manage tasks.
- An authenticated user can list all tasks with or without a "isDone" state filter, and then access a particular dedicated list.
- An authenticated user can create any tasks.
- An authenticated user can toggle any tasks "isDone" state by marking selected one as "done/undone".
- An authenticated user can update (edit) any tasks as last editor.
- An authenticated user can only delete his own created tasks.

Administrator features:
- An authenticated administrator (admin) can also manage other registered users.
- An authenticated administrator benefits from the same user permissions on tasks.
- An authenticated administrator can list all users accounts.
- An authenticated administrator can create another user account and define his roles (user, admin).
- An authenticated administrator can update all users accounts and redefine his roles.
- An authenticated administrator can delete all users accounts.

###### *Please note that this project uses these libraries or Symfony bundles:*
Faker PHP library (in order to add custom data fixtures)
> https://github.com/FakerPHP/Faker

Doctrine test bundle (in order to manage automated tests and easily rollback database transaction)
> https://github.com/dmaicher/doctrine-test-bundle

### Local installation (can be used on deployment with ssh access with some adaptations for environment variables)

#### 1. Clone project repository master branch on GitHub with:
```
$ git clone https://github.com/sjeguedes/symfonyTDAC.git
```

#### 2. Configure project needed particular data and your own database parameters with environment variables in `env.local` file using `.env` provided example file:

###### *Prefer use a `env.<your_environment>.local` file per environment combined to `env.local.php` to manage each environment.*
```
# Application environnement and secret
APP_ENV=your_environment # e.g. "dev", "test" or "prod"
APP_SECRET=your_secret

# Database configuration (example here with MySQL)
DATABASE_URL=mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=5.7
```
#### 3. Adapt Doctrine "dbal" section configuration (driver and server_version) to your system requirements in `config/packages/doctrine.yaml` file if needed:

###### *For instance, this is needed if you use another driver such as PostgreSQL, MariaDB, etc... instead of MySQL.*

#### 4. Install dependencies defined in composer.json:
```
# Development (dev) environment
$ composer install
# Production (prod) environnement
$ composer install --no-dev --no-scripts --optimize-autoloader
```

#### 5. Create database and schema with Doctrine migrations located in `migrations` folder:
```
# Create database
$ php bin/console doctrine:database:create
```

###### *Use migrations instead of updating schema!*
```
# Create schema
$ php bin/console doctrine:migrations:migrate
```

#### 6. Add starting set of data with Doctrine fixtures located in `src/DataFixtures`:
```
$ php bin/console doctrine:fixtures:load
# or add "-n" option to avoid console interactivity
$ php bin/console doctrine:fixtures:load -n
```

#### 7. Application automated tests:
###### *Please note that 3 automated test suites were made with PHPUnit test framework, to maintain this project correctly.* 
###### *You can have a look at `tests` folder divided in 3 sub folders (unit, integration, functional) and `env.test` example file:*
For local installation:
```
# You can or simply use option "--env=test" for each command or switch to "test" environnement 
and install "test" particular database thanks to ".env.test.local" file configuration.
# e.g. with ".env.local.php"
$ composer dump-env test
# Then follow the same process as above to create "test" database and generate fixtures for this environment.

# Execute all existing test suites after "test" environnement installation:
$ php bin/phpunit
# or to be more explicit:
$ php bin/phpunit tests
# or:
$ php bin/phpunit --testsuite 'Project Tests Suite'

# Execute only unit test suite:
$ php bin/phpunit tests/unit
# or:
$ php bin/phpunit --testsuite 'Project Unit Tests Suite'

# Execute only integration test suite:
$ php bin/phpunit tests/integration
# or:
$ php bin/phpunit --testsuite 'Project Integration Tests Suite'

# Execute only functional test suite:
$ php bin/phpunit tests/functional
# or:
$ php bin/phpunit --testsuite 'Project Functional Tests Suite'
```
###### *You can have a look at `phpunit.xml.dist` example file located in project root folder, to manage PHPUnit configuration*
PHPUnit current documentation: 
> https://phpunit.readthedocs.io

#### 8. Project contribution:
If you are interested in improving this project you can follow our guidelines defined in `CONTRIBUTING.md`.  
You are welcome to make this application evolve.

 
