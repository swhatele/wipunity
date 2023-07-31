<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRepliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared('CREATE TABLE replies (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `discussion_id` BIGINT NOT NULL, 
            `user_id` BIGINT NOT NULL,
            `reply_text` LONGTEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_replies_user_id` (`user_id` ASC),
            INDEX `idx_replies_discussion_id` (`discussion_id` ASC),
            INDEX `idx_replies_created_at` (`created_at` ASC))
            PARTITION BY RANGE (`id`) (
                PARTITION p0 VALUES LESS THAN (1),
                PARTITION p1 VALUES LESS THAN (10000),
                PARTITION p2 VALUES LESS THAN (20000),
                PARTITION p3 VALUES LESS THAN (30000),
                PARTITION p4 VALUES LESS THAN (40000),
                PARTITION p5 VALUES LESS THAN (50000),
                PARTITION p6 VALUES LESS THAN (60000),
                PARTITION p7 VALUES LESS THAN (70000),
                PARTITION p8 VALUES LESS THAN (80000),
                PARTITION p9 VALUES LESS THAN (90000),
                PARTITION p10 VALUES LESS THAN (100000),
                PARTITION p11 VALUES LESS THAN (110000),
                PARTITION p12 VALUES LESS THAN (120000),
                PARTITION p13 VALUES LESS THAN (130000),
                PARTITION p14 VALUES LESS THAN (140000),
                PARTITION p15 VALUES LESS THAN (150000),
                PARTITION p16 VALUES LESS THAN (160000),
                PARTITION p17 VALUES LESS THAN (170000),
                PARTITION p18 VALUES LESS THAN (180000),
                PARTITION p19 VALUES LESS THAN (190000),
                PARTITION p20 VALUES LESS THAN (200000),
                PARTITION p21 VALUES LESS THAN (210000),
                PARTITION p22 VALUES LESS THAN (220000),
                PARTITION p23 VALUES LESS THAN (230000),
                PARTITION p24 VALUES LESS THAN (240000),
                PARTITION p25 VALUES LESS THAN (250000),
                PARTITION p26 VALUES LESS THAN (260000),
                PARTITION p27 VALUES LESS THAN (270000),
                PARTITION p28 VALUES LESS THAN (280000),
                PARTITION p29 VALUES LESS THAN (290000),
                PARTITION p30 VALUES LESS THAN (300000),
                PARTITION p31 VALUES LESS THAN (310000),
                PARTITION p32 VALUES LESS THAN (320000),
                PARTITION p33 VALUES LESS THAN (330000),
                PARTITION p34 VALUES LESS THAN (340000),
                PARTITION p35 VALUES LESS THAN (350000),
                PARTITION p36 VALUES LESS THAN (360000),
                PARTITION p37 VALUES LESS THAN (370000),
                PARTITION p38 VALUES LESS THAN (380000),
                PARTITION p39 VALUES LESS THAN (390000),
                PARTITION p40 VALUES LESS THAN (400000),
                PARTITION p41 VALUES LESS THAN (410000),
                PARTITION p42 VALUES LESS THAN (420000),
                PARTITION p43 VALUES LESS THAN (430000),
                PARTITION p44 VALUES LESS THAN (440000),
                PARTITION p45 VALUES LESS THAN (450000),
                PARTITION p46 VALUES LESS THAN (460000),
                PARTITION p47 VALUES LESS THAN (470000),
                PARTITION p48 VALUES LESS THAN (480000),
                PARTITION p49 VALUES LESS THAN (490000),
                PARTITION p50 VALUES LESS THAN (500000),
                PARTITION p51 VALUES LESS THAN (510000),
                PARTITION p52 VALUES LESS THAN (520000),
                PARTITION p53 VALUES LESS THAN (530000),
                PARTITION p54 VALUES LESS THAN (540000),
                PARTITION p55 VALUES LESS THAN (550000),
                PARTITION p56 VALUES LESS THAN (560000),
                PARTITION p57 VALUES LESS THAN (570000),
                PARTITION p58 VALUES LESS THAN (580000),
                PARTITION p59 VALUES LESS THAN (590000),
                PARTITION p60 VALUES LESS THAN (600000),
                PARTITION p61 VALUES LESS THAN (610000),
                PARTITION p62 VALUES LESS THAN (620000),
                PARTITION p63 VALUES LESS THAN (630000),
                PARTITION p64 VALUES LESS THAN (640000),
                PARTITION p65 VALUES LESS THAN (650000),
                PARTITION p66 VALUES LESS THAN (660000),
                PARTITION p67 VALUES LESS THAN (670000),
                PARTITION p68 VALUES LESS THAN (680000),
                PARTITION p69 VALUES LESS THAN (690000),
                PARTITION p70 VALUES LESS THAN (700000),
                PARTITION p71 VALUES LESS THAN (710000),
                PARTITION p72 VALUES LESS THAN (720000),
                PARTITION p73 VALUES LESS THAN (730000),
                PARTITION p74 VALUES LESS THAN (740000),
                PARTITION p75 VALUES LESS THAN (750000),
                PARTITION p76 VALUES LESS THAN (760000),
                PARTITION p77 VALUES LESS THAN (770000),
                PARTITION p78 VALUES LESS THAN (780000),
                PARTITION p79 VALUES LESS THAN (790000),
                PARTITION p80 VALUES LESS THAN (800000),
                PARTITION p81 VALUES LESS THAN (810000),
                PARTITION p82 VALUES LESS THAN (820000),
                PARTITION p83 VALUES LESS THAN (830000),
                PARTITION p84 VALUES LESS THAN (840000),
                PARTITION p85 VALUES LESS THAN (850000),
                PARTITION p86 VALUES LESS THAN (860000),
                PARTITION p87 VALUES LESS THAN (870000),
                PARTITION p88 VALUES LESS THAN (880000),
                PARTITION p89 VALUES LESS THAN (890000),
                PARTITION p90 VALUES LESS THAN (900000),
                PARTITION p91 VALUES LESS THAN (910000),
                PARTITION p92 VALUES LESS THAN (920000),
                PARTITION p93 VALUES LESS THAN (930000),
                PARTITION p94 VALUES LESS THAN (940000),
                PARTITION p95 VALUES LESS THAN (950000),
                PARTITION p96 VALUES LESS THAN (960000),
                PARTITION p97 VALUES LESS THAN (970000),
                PARTITION p98 VALUES LESS THAN (980000),
                PARTITION p99 VALUES LESS THAN (990000),
                PARTITION p100 VALUES LESS THAN (1000000),
                PARTITION p101 VALUES LESS THAN MAXVALUE
            )'
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('replies');
    }
}
