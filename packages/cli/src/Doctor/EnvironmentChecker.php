<?php
namespace PhpWails\Cli\Doctor;
final class EnvironmentChecker {
    public function checks(): array {
        return [
            ['checkId'=>'php.version','status'=>version_compare(PHP_VERSION,'8.2.0','>=')?'pass':'fail','detectedValue'=>PHP_VERSION,'requiredValue'=>'>=8.2.0'],
            ['checkId'=>'php.json','status'=>extension_loaded('json')?'pass':'fail','detectedValue'=>extension_loaded('json')?'enabled':'missing','requiredValue'=>'enabled'],
            ['checkId'=>'php.phar','status'=>extension_loaded('phar')?'pass':'fail','detectedValue'=>extension_loaded('phar')?'enabled':'missing','requiredValue'=>'enabled'],
        ];
    }
}
