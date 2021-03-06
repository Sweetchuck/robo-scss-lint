
# Robo task wrapper for scss-lint

[![Build Status](https://travis-ci.org/Sweetchuck/robo-scss-lint.svg?branch=master)](https://travis-ci.org/Sweetchuck/robo-scss-lint)
[![codecov](https://codecov.io/gh/Sweetchuck/robo-scss-lint/branch/master/graph/badge.svg)](https://codecov.io/gh/Sweetchuck/robo-scss-lint)

Bridge between [Robo](http://robo.li) and [scss-lint](https://rubygems.org/gems/scss_lint)

```php
<?php

use Sweetchuck\Robo\ScssLint\ScssLintTaskLoader;

class RoboFile extends \Robo\Tasks
{
    use ScssLintTaskLoader;

    public function lintScssMinimal()
    {
        // bundle exec scss-lint
        return $this->taskScssLintFiles();
    }
}
```
