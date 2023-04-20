# mysql数据同步

#### 默认会同步表结构，如果安装了Swoole扩展会自动选择多进程同步，一个进程同步一个表,没有swoole借助第三方composer包实现多进程同步

# 步骤
#### 执行composer install
#### 执行doc/sql/sync.sql，在t_sync_tables中维护需要同步的表及条件
#### 然后运行run.php
