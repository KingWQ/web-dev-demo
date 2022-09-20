<?php
namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class Table extends Command
{
    protected function configure()
    {
        $this->setName('gene-table')
            ->setDescription('生成表结构');
    }

    protected function execute(Input $input, Output $output)
    {

        $this->generateTest();
        $output->writeln("Done");
    }

    protected function generateTest()
    {
        $str = <<<EOF
CREATE TABLE `ms_test` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL COMMENT '所属用户id',
  `data_key` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '数据唯一key',
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '数据名称',
  `country_name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '国家名称',
  `country_id` int(11) unsigned NOT NULL COMMENT '国家id',
  `country_price` decimal(12,4) unsigned NOT NULL COMMENT '国家价格',
  `country_short_name` char(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '国家简写',
  `total_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上传数据数量',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除：0=>未删除;1=>已删除',
  `deleted_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  `gmt_create` datetime DEFAULT CURRENT_TIMESTAMP,
  `gmt_modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uni_user_name` (`user_id`,`name`) USING BTREE,
  UNIQUE KEY `uni_data_key` (`data_key`),
  KEY `idx_gmt_create` (`gmt_create`),
  KEY `idx_user_deleted` (`user_id`,`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='test表';
EOF;
        $sql = <<<EOF
CREATE TABLE `ms_data_%s` (
  `data_id` bigint(20) unsigned NOT NULL COMMENT '所属数据id',
  `phone` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '手机号',
  PRIMARY KEY (`data_id`,`phone`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='data第%s分表';
EOF;

        for($i=0; $i<100; $i++){
            $str .= "\r\n".sprintf($sql, $i, $i);
        }
        file_put_contents("test.sql",$str);
    }

}