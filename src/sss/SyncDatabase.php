<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019/6/3
 * Time: 10:28
 */

namespace Sss;

/**
 * Class SyncDatabase
 * @package Sss
 */
class SyncDatabase
{

    protected static function getConnection($config)
    {
        $options = [
            // \PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,//禁止多语句查询
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '" . $config['charset'] . "';",// 设置客户端连接字符集
            \PDO::ATTR_TIMEOUT => 10,// 设置超时
            \PDO::ATTR_PERSISTENT => true,// 长链接
        ];
        $dsn = sprintf('mysql:dbname=%s;host=%s;port=%d', $config['database'], $config['host'], $config['port']);
        $pdo = new \PDO($dsn, $config['user'], $config['password'], $options);
        // $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false); //禁用模拟预处理
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->config = $config;
        return $pdo;
    }

    /*
     * swoole多进程同步
     */
    public static function swooleRun($from, $to, $tableConfList, $pageSize = 1000)
    {
        foreach ($tableConfList as $tableConf) {
            $process = new \Swoole\Process(function (\Swoole\Process $process) {
                $readData = $process->read(PHP_INT_MAX);
                $readData = json_decode($readData, true);
                (new SyncDatabaseProcess($readData['from'], $readData['to'], $readData['pageSize']))->sync($readData['tableConf']);
            }, false, true);

            $process->write(json_encode([
                'from' => $from,
                'to' => $to,
                'pageSize' => $pageSize,
                'tableConf' => $tableConf
            ]));
            $process->start();
            // usleep(400000);
        }
    }

    /*
     * 借助第三方包使用多进程同步
     */
    public static function taskRun($from, $to, $tableConfList, $pageSize = 1000)
    {
        $baseDir = getcwd();
        $outputDir = $baseDir . '/output';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        \BaAGee\AsyncTask\TaskScheduler::init($outputDir . '/lock', 20, $outputDir);
        $task = \BaAGee\AsyncTask\TaskScheduler::getInstance();
        foreach ($tableConfList as $tableConf) {
            $task->runTask(\Sss\SyncTask::class, [serialize($from), serialize($to), serialize($tableConf), serialize($pageSize)]);
            echo sprintf('%s同步任务已启动，日志路径：%s' . PHP_EOL, $tableConf['table'], $outputDir);
        }
    }



    public static function taskRunAll($from, $to, $pageSize = 1000)
    {
        echo date("Y-m-d") . " taskRunAll start at " . date("Y-m-d H:i:s") . "\r\n";
        $baseDir = getcwd();
        $outputDir = $baseDir . '/output';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $maxNum  = 10;

        \BaAGee\AsyncTask\TaskScheduler::init($outputDir . '/lock', $maxNum, $outputDir);
        //从同步表取数据 一次取10个

        $conn = self::getConnection($to);
        $conn->exec("UPDATE t_sync_tables SET sync_state=0, sync_time=null");

        $overFlag = true;
        $num = 0;
        while($overFlag) {
            $lockNum = file_get_contents('./output/lock');
            $lockNum = empty($lockNum) ? 0 : $lockNum;
            $handleNum = $maxNum - $lockNum;

            if($handleNum > 0) {
                $sql = sprintf('SELECT * FROM t_sync_tables WHERE sync_state=0 ORDER BY weight DESC LIMIT %s', $handleNum);
                echo $sql . "\r\n";
                $stmt = $conn->prepare($sql);
                $rrr = $stmt->execute();
                if ($rrr === false || $stmt == false) {
                    throw new \Exception('taskRunAll 查询数据失败:' . $sql);
                }
                $tables = [];
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    if($row != null) {
                        $tables[] = $row;
                    }
                }
                if(empty($tables)) {
                    $overFlag = false;
                    break;
                }

                $task = \BaAGee\AsyncTask\TaskScheduler::getInstance();
                foreach ($tables as $row) {
                    echo json_encode($row);
                    if($row == null) {
                        $overFlag = false;
                        break;
                    }
                    echo $row['table_name']."\r\n";
                    $tableConf = [
                        'table' => $row['table_name'],
                        'truncate' => true,
//                        'where' => $row['sync_where']
                    ];
                    if(!empty($row['sync_where'])) {
                        $tableConf['where'] = $row['sync_where'];
                    }
                    echo json_encode($tableConf);
                    $task->runTask(\Sss\SyncTask::class, [serialize($from), serialize($to), serialize($tableConf), serialize($pageSize)]);
                    echo sprintf('%s同步任务已启动，日志路径：%s' . PHP_EOL, $tableConf['table'], $outputDir);
                }

            }
            sleep(1);
            $num += 1;
        }

        echo date("Y-m-d") . " taskRunAll end at " . date("Y-m-d H:i:s") . "\r\n";
//        foreach ($tableConfList as $tableConf) {
//            $task->runTask(\Sss\SyncTask::class, [serialize($from), serialize($to), serialize($tableConf), serialize($pageSize)]);
//            echo sprintf('%s同步任务已启动，日志路径：%s' . PHP_EOL, $tableConf['table'], $outputDir);
//        }
    }

    public static function run($from, $to, $tableConfList, $pageSize = 1000)
    {
        $process = new SyncDatabaseProcess($from, $to, $pageSize);
        foreach ($tableConfList as $value) {
            $process->sync($value);
        }
    }
}
