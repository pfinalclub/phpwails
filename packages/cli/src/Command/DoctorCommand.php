<?php
namespace PhpWails\Cli\Command;
use PhpWails\Cli\Doctor\EnvironmentChecker;
final class DoctorCommand {
    public function run(bool $json = false): int {
        $checks = (new EnvironmentChecker())->checks();
        if ($json) echo json_encode(['command'=>'doctor','status'=>array_filter($checks,fn($c)=>$c['status']==='fail')?'failed':'success','data'=>$checks,'diagnostics'=>[],'durationMs'=>0], JSON_PRETTY_PRINT).PHP_EOL;
        else foreach ($checks as $check) printf("%-24s %-7s %s\n", $check['checkId'], strtoupper($check['status']), $check['detectedValue']);
        return array_filter($checks,fn($c)=>$c['status']==='fail') ? 3 : 0;
    }
}
