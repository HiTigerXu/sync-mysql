# mysql数据同步

#### 默认会同步表结构，借助第三方composer包实现多进程同步
#### 走Swoole方式暂时没有进行固定进程数轮询同步改造，先进行屏蔽
~~如果安装了Swoole扩展会自动选择多进程同步~~

# 步骤
#### 执行composer install
#### 执行doc/sql/sync.sql，在t_sync_tables中维护需要同步的表及条件
#### 然后运行run.php
