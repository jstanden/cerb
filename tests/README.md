# Running tests

## Install Dependencies

`$ cd tests`
`$ composer install`

## Install ChromeDriver for UI tests

It's suggested to use Headless Chrome (the default) for running the UI tests. You need to install ChromeDriver for that, which can be done via Homebrew on macOS with the following:

`$ brew install chromedriver`

In a terminal window, you need to startup chromedriver with the following command before running tests:

`$ chromedriver --port=4444`

You can add `--verbose` if you want detailed logging from Chrome.

## Running tests

Initial DB install: `vendor/bin/phpunit -c phpunit.cerb.install.xml`

UI tests: `vendor/bin/phpunit -c phpunit.cerb.eval.xml`

