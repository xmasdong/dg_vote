<?php
/**
 * 高校投票
 *
 * @author 冬瓜
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Dg_voteModuleReceiver extends WeModuleReceiver
{

    public function receive()
    {
        global $_W;
        load()->model('mc');
        $fromuser = $this->message['from'];
        if ($this->message['msgtype'] == 'event') {
            if ($this->message['event'] == 'subscribe') {
            } elseif ($this->message['event'] == 'unsubscribe') {
                if (pdo_delete(
                    'dg_vote_user',
                    array('user' => $fromuser)
                )) {
                    $cookie_datas = cache_load($fromuser);
                    $banjiname = $cookie_datas['banjiname'];
                    $bianhao = $cookie_datas['bianhao'];
                    $banji = cache_load($bianhao);
                    $vote = $banji['vote']-1;
                    $cookie_data = array(
                        'bianhao' => $banji['bianhao'],
                        'banjiname' => $banjiname,
                        'vote' => $vote,//减少1票
                    );
                    $totalvote_data = cache_load('totalvote');
                    $totalvote = $totalvote_data['totalvote'] - 1;
                    $totaluser = $totalvote_data['totaluser'] - 1;
                    $voe_data = array(
                        'totalvote' => $totalvote,
                        'totaluser' => $totaluser,
                    );
                    cache_write('totalvote', $voe_data);////总票数
                    cache_delete($fromuser);
                    cache_write($bianhao, $cookie_data);


                    pdo_update('dg_vote_data',array(
                        'vote'=>$vote,
                    ),array('bianhao'=>strtoupper($bianhao)));
                }
            }
        }

    }
}