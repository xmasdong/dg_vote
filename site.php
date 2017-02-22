<?php
/**
 * 高校常用投票系统模块处理程序
 *
 * @author 冬瓜
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Dg_voteModuleSite extends WeModuleSite
{
    public $table_reply  = 'dg_vote_reply';
    public $table_data  = 'dg_vote_data';
    //参数设置

    //参数设置
    public function doMobileIndex(){
        global $_GPC,$_W;
        if (!empty($_GPC['rid'])) {
            $reply = pdo_fetch("SELECT * FROM ".tablename($this->table_reply)." WHERE rid = :rid ORDER BY `id` DESC",
                array(':rid' => $_GPC['rid']));
        }
        $totalvote_data=cache_load('totalvote');
        $totalvote=$totalvote_data['totalvote'];
        if(!$totalvote){
            $totalvote=0;
        }
        $totaluser=$totalvote_data['totaluser'];
        $list = pdo_fetchall("SELECT * FROM " . tablename('dg_vote_data') . "ORDER BY vote DESC");
        $i=1;
        $copyright=$reply['copyright'];
        $title=$reply['title'];

        include $this->template('rand');
    }


    public function doWebList(){
        global $_GPC,  $_W;
        $uniacid=$_W["uniacid"];
        $pindex = max(1, intval($_GPC['page']));
        $psize = 100;
        $where ="";
        $list = pdo_fetchall("SELECT *  from ".tablename($this->table_data)." where uniacid='{$uniacid}' $where order by id asc LIMIT ". ($pindex -1) * $psize . ',' .$psize );
        $total = pdo_fetchcolumn("SELECT COUNT(*)  from ".tablename($this->table_data)." where uniacid='{$uniacid}' $where order by id asc");
        $pager = pagination($total, $pindex, $psize);
        load()->func('tpl');
        include $this->template('list');
    }

    public function doWebAdd() {
        global $_GPC,  $_W;
        load()->func('tpl');
        $id = intval($_GPC['id']);
        if (!empty($id)) {
            $item = pdo_fetch("SELECT * FROM ".tablename($this->table_data)." WHERE id = :id" , array(':id' => $id));
            if (empty($item)) {
                message('抱歉，班级不存在或是已经删除！', '', 'error');
            }

            $bianhao=empty($item['bianhao'])? 'tXXXX':$item['bianhao'];
            $vote=empty($item['vote'])? 0:$item['vote'];
        }
        if(checksubmit('submit')) {
            $data = array(
                'uniacid'	=>	$_W['uniacid'],
                'banjiname'	=>	$_GPC['banjiname'],
                'bianhao'	=>	$_GPC['bianhao'],
                'vote'		=>	$_GPC['vote'],
            );
            if (empty($id)) {
                pdo_insert($this->table_data, $data);
                message('班级添加成功！', referer(), 'success');
            }else{
                pdo_update($this->table_data, $data, array('id' => $id));
                message('班级编辑成功！', referer(), 'success');
            }
        }
        include $this->template('add');
    }

}