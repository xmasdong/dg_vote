<?php
/**
 * 高校常用投票系统模块定义
 *
 * @author 冬瓜
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Dg_voteModule extends WeModule {
    public $table_reply  = 'dg_vote_reply';

    public function fieldsFormDisplay($rid) {
        //要嵌入规则编辑页的自定义内容，这里 $rid 为对应的规则编号，新增时为 0
        global $_W;
        load()->func('tpl');
        if (!empty($rid)) {
            $reply = pdo_fetch("SELECT * FROM ".tablename($this->table_reply)." WHERE rid = :rid ORDER BY `id` DESC",
                array(':rid' => $rid));
        }

        $reply['starttime'] = empty($reply['starttime']) ? strtotime(date('Y-m-d')) : $reply['starttime'];
        $reply['endtime'] = empty($reply['endtime']) ? TIMESTAMP : $reply['endtime'] + 86399;
        $reply['first_title'] = empty($reply['first_title']) ? "你正在为#banjiname#投票,当前票数#vote#,请回复验证码以完成投票!" : $reply['first_title'];
        $reply['second_title'] = empty($reply['second_title']) ? "你的验证码为:#code#" : $reply['second_title'];
        $reply['third_title'] = empty($reply['third_title']) ? "点击查看排行榜" : $reply['third_title'];
        $reply['third_url'] = empty($reply['third_url']) ? $_W['siteroot']."app".str_replace('./','/',$this->createMobileUrl('index',array('rid'=>$rid)) ): $reply['third_url'];
        $reply['third_img'] = empty($reply['third_img']) ? '': $reply['third_img'];

        $reply['fourth_title'] = empty($reply['fourth_title']) ? '' : $reply['fourth_title'];
        $reply['fourth_url'] = empty($reply['fourth_url']) ? '': $reply['fourth_url'];
        $reply['fourth_img'] = empty($reply['fourth_img']) ? '': $reply['fourth_img'];

        $reply['fifth_title'] = empty($reply['fifth_title']) ? '' : $reply['fifth_title'];
        $reply['fifth_url'] = empty($reply['fifth_url']) ? '': $reply['fifth_url'];
        $reply['fifth_img'] = empty($reply['fifth_img']) ? '': $reply['fifth_img'];


        $reply['title'] = empty($reply['title']) ? '': $reply['title'];
        $reply['copyright'] = empty($reply['copyright']) ? '': $reply['copyright'];


        include $this->template('form');

    }

    public function fieldsFormValidate($rid = 0) {
        //规则编辑保存时，要进行的数据验证，返回空串表示验证无误，返回其他字符串将呈现为错误提示。这里 $rid 为对应的规则编号，新增时为 0
        return '';
    }

    public function fieldsFormSubmit($rid) {
        //规则验证无误保存入库时执行，这里应该进行自定义字段的保存。这里 $rid 为对应的规则编号
        global $_GPC, $_W;
        $insert = array(
            'rid' => $rid,
            'uniacid'=>$_W["uniacid"],
            'title' => $_GPC['title'],
            'copyright' => $_GPC['copyright'],
            'fengmian' => $_GPC['fengmian'],
            'first_title' => $_GPC['first_title'],
            'second_title' => $_GPC['second_title'],
            'third_title' => $_GPC['third_title'],
            'third_url' => $_W['siteroot']."app".str_replace('./','/',$this->createMobileUrl('index',array('rid'=>$rid)) ),
            'third_img' => $_GPC['third_img'],
            'fourth_title' => $_GPC['fourth_title'],
            'fourth_url' => $_GPC['fourth_url'],
            'fourth_img' => $_GPC['fourth_img'],
            'fifth_title' => $_GPC['fifth_title'],
            'fifth_url' => $_GPC['fifth_url'],
            'fifth_img' => $_GPC['fifth_img'],
            'starttime' => strtotime($_GPC['datelimit']['start']),
            'endtime' => strtotime($_GPC['datelimit']['end']),
            'status' => intval($_GPC['status'])
        );
        if (empty($id)) {
            pdo_insert($this->table_reply, $insert);
            $this->saveSettings($insert);
        } else {
            pdo_update($this->table_reply, $insert, array('rid' => $rid));
            $this->saveSettings($insert);
        }

    }

    public function ruleDeleted($rid) {
        //删除规则时调用，这里 $rid 为对应的规则编号
        global $_W;
        load()->func('file');
        pdo_delete($this->table_reply, "rid = ".$rid);
        return true;
    }

}