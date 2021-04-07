# Workflow

[![Build Status](https://travis-ci.com/pluf/workflow.svg?branch=master)](https://travis-ci.com/pluf/workflow)
[![codecov](https://codecov.io/gh/pluf/workflow/branch/master/graph/badge.svg)](https://codecov.io/gh/pluf/workflow)
[![Coverage Status](https://coveralls.io/repos/github/pluf/workflow/badge.svg)](https://coveralls.io/github/pluf/workflow)
[![Maintainability](https://api.codeclimate.com/v1/badges/9e1457dbf2f0bcc8b953/maintainability)](https://codeclimate.com/github/pluf/workflow/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/9e1457dbf2f0bcc8b953/test_coverage)](https://codeclimate.com/github/pluf/workflow/test_coverage)


A workflow module fo pluf.

## Contributing

If you would like to contribute to Pluf, please read the README and CONTRIBUTING documents.

The most important guidelines are described as follows:

>All code contributions - including those of people having commit access - must go through a pull request and approved by a core developer before being merged. This is to ensure proper review of all the code.

Fork the project, create a feature branch, and send us a pull request.

To ensure a consistent code base, you should make sure the code follows the PSR-2 Coding Standards.



https://github.com/hekailiang/squirrel



## What is it?

Workflow is aimed to provide a **lightweight**, highly **flexible** and **extensible**, **diagnosable**, **easy use** and **type safe** PHP 8 state machine implementation for enterprise usage.

Here is the state machine diagram which describes the state change of an ATM:

![ATMStateMachine](http://hekailiang.github.io/squirrel/images/ATMStateMachine.png)

The sample code could be found in folder *"tests\ATMStateMachine.php"*.

## Composer

Pluf Workflow has been deployed to pakcagest (php composer repository) repository, so you only need to add following  dependency to the composer.json.


```json
"pluf\workflow": "7.x"
```







