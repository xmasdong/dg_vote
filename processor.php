<?php
/**
 * 高校常用投票系统模块处理程序
 *
 * @author 冬瓜
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Dg_voteModuleProcessor extends WeModuleProcessor
{
    public $table_reply = 'dg_vote_reply';

    public function respond()
    {
        if($this->module['config']['status']==1){//启动
        $content = $this->message['content'];
        $openid = $this->message['from'];
        //这里定义此模块进行消息处理时的具体过程, 请查看微擎文档来编写你的代码
        $content = strtolower($content);
        if ($content == "取消") {//清楚cookie
            cache_clean();
            $this->endContext();
            return $this->respText("清除完毕");
        }
        if($this->module['config']['starttime']>TIMESTAMP){
            return $this->respText("活动还没开始,敬请期待");

        }
        if($this->module['config']['endtime']<TIMESTAMP){

            return $this->respText("活动已经结束");

        }
        if (!cache_load($content)) {//班级第一次被投票,需要初始化数据,例如班级编号对应的班级,投票的人是否第一次进行投票等
            //检查下该童鞋是否已经投过票了
            if (cache_load($openid)) {
                $isvotes = cache_load($openid);
                $isvote = $isvotes['isvote'];
                if ($isvote == 1) {//已经进行投票
                    $news[] = array(
                        'title' => "你已经为" . $isvotes['banjiname'] . "投过票啦!",
                        'description' => "点击查看排行榜!",
                        'picurl' => $this->module['config']['third_img'],
                        'url' => $this->module['config']['third_url'],
                    );
                    return $this->respNews($news);
                    //return $this->respText("你已经为".$isvotes['banjiname']."投过票啦,一人只能投一票,邀请好友一起投票吧!");
                }
            }
            if ($this->inContext) {//回复验证码阶段,验证码输入正确则票数加一票
                if (cache_load($openid)) {
                    $cookie_datas = cache_load($openid);
                    if (!$cookie_datas['code']) {
                    } else {//投票逻辑正式开始
                        if (strtolower($cookie_datas['code']) == $content) {//用户验证码跟之前的验证码一致,投票成功

                            $banjiname = $cookie_datas['banjiname'];
                            $bianhao = $cookie_datas['bianhao'];
                            $banji = cache_load($bianhao);
                            $vote = $banji['vote'] + 1;


                            $cookie_user = array(
                                'id' => '',
                                'user' => $openid,
                                'banjiname' => $banjiname,//为哪个班级投票
                                'bianhao' => $bianhao,
                                'time' => time(),
                                'isvote' => 1,//是否已经投过票,0还没,1投了

                            );

                            $cookie_data = array(
                                'bianhao' => $banji['bianhao'],
                                'banjiname' => $banjiname,
                                'vote' => $vote,//增加1票
                            );

                            if (!cache_load('totalvote')) {
                                $voe_data = array(
                                    'totalvote' => 0,
                                    'totaluser' => 0,
                                );
                                cache_write('totalvote', $voe_data);//
                            }

                            $totalvote_data = cache_load('totalvote');
                            $totalvote = $totalvote_data['totalvote'] + 1;
                            $totaluser = $totalvote_data['totaluser'] + 1;
                            $voe_data = array(
                                'totalvote' => $totalvote,
                                'totaluser' => $totaluser,
                            );
                            cache_write('totalvote', $voe_data);////总票数
                            cache_write($openid, $cookie_user);//将投票的童鞋信息写入缓存,已经投票
                            cache_write($bianhao, $cookie_data);
                            $this->endContext();
                            // if($vote%10==0){//每10票存一次数据库
                            pdo_update('dg_vote_data', array(
                                'bianhao' => $banji['bianhao'],
                                'banjiname' => $banjiname,
                                'vote' => $vote,//增加1票
                            ), array('bianhao' => strtoupper($bianhao)));
                            pdo_insert('dg_vote_user', $cookie_user);

                            // }

                            $news[] = array(
                                'title' => "你成功为" . $banjiname . "投了一票!",
                                'description' => "当前票数为:" . $vote . "\n继续加油!",
                                'picurl' => $this->module['config']['third_img'],
                                'url' => $this->module['config']['third_url'],
                            );
                            return $this->respNews($news);
                            // return $this->respText("你成功为".$banjiname."投了一票\n当前票数为:".$vote."\n继续加油!");


                        } else return $this->respText("你输入的验证码有误,请重新输入");
                    }
                } else {
                    $this->endContext();
                }
            } else {
                //查数据库,查该编号对应的班级
                $res = pdo_fetch('SELECT * FROM ' . tablename('dg_vote_data') . 'WHERE bianhao=:BIANHAO', array(':BIANHAO' => strtoupper($content)));
                if ($res) {//若存在,则进行投票逻辑处理.

                    $banjiname = $res['banjiname'];
                    $bianhao = $res['bianhao'];
                    $vote = $res['vote'];

                    $cookie_data = array(
                        'bianhao' => $bianhao,
                        'banjiname' => $banjiname,
                        'vote' => 0,//初始化,
                    );


                    $code = $this->getrandcode();
                    $cookie_user = array(//注册投票用户
                        'user' => $openid,
                        'bianhao' => $bianhao,
                        'banjiname' => $banjiname,//为哪个班级投票
                        'isvote' => 0,//是否已经投过票,0还没,1投了
                        'code' => $code,
                    );

                    cache_write($content, $cookie_data);//将该班级写入缓存

                    cache_write($openid, $cookie_user);//将投票的童鞋信息写入缓存

                    $this->beginContext(600);
                    $news = array();
                    $first_title = str_replace('#banjiname#', $banjiname, $this->module['config']['first_title']);
                    $first_title = str_replace('#vote#', $vote, $first_title);

                    $news[] = array(
                        'title' => $first_title,
                        'description' => '',
                        'picurl' => $this->module['config']['fengmian'],
                        'url' => $this->createMobileUrl('index'),
                    );
                    $second_title = str_replace('#code#', $code, $this->module['config']['second_title']);

                    $news[] = array(
                        'title' => $second_title,
                        'description' => '',
                        'picurl' => '',
                        'url' => $this->createMobileUrl('index'),
                    );

                    if (!empty($this->module['config']['third_title'])) {

                        $news[] = array(
                            'title' => $this->module['config']['third_title'],
                            'description' => '',
                            'picurl' => $this->module['config']['third_img'],
                            'url' => $this->module['config']['third_url'],
                        );

                    }
                    if (!empty($this->module['config']['fourth_title'])) {

                        $news[] = array(
                            'title' => $this->module['config']['fourth_title'],
                            'description' => '',
                            'picurl' => $this->module['config']['fourth_img'],
                            'url' => $this->module['config']['fourth_url'],
                        );

                    }

                    if (!empty($this->module['config']['fifth_title'])) {

                        $news[] = array(
                            'title' => $this->module['config']['fifth_title'],
                            'description' => '',
                            'picurl' => $this->module['config']['fifth_img'],
                            'url' => $this->module['config']['fifth_url'],
                        );

                    }
                    return $this->respNews($news);

                    // return $this->respText('你正在为' . $first_title . "投票\n确认投票请回复中括号里面的验证码:[" . $code . "]\n5分钟回复有效(不区分大小写)");

                } else {//若投票码不存在
                    $this->endContext();
                    return $this->respText('你输入的投票码有误,请输入正确的班级编号!');
                }

            }
        } else {//非第一次,注册投票用户,如果该用户已经投过票则返回数据显示不用投,否则注册该用户
            $dat = cache_load($content);
            $bianhao = $dat['bianhao'];
            $banjiname = $dat['banjiname'];
            $vote = $dat['vote'];

            if (cache_load($openid)) {
                $isvotes = cache_load($openid);
                $isvote = $isvotes['isvote'];
                if ($isvote == 1) {
                    return $this->respText("你已经为" . $isvotes['banjiname'] . "投过票啦,一人只能投一票,邀请好友一起投票吧!");
                } else {
                    $code = $this->getrandcode();
                    $cookie_user = array(//注册投票用户
                        'user' => $openid,
                        'bianhao' => $bianhao,
                        'banjiname' => $banjiname,//为哪个班级投票
                        'isvote' => 0,//是否已经投过票,0还没,1投了
                        'code' => $code,
                    );
                    cache_write($openid, $cookie_user);//将投票的童鞋信息写入缓存
                    $this->beginContext(600);
                    $news = array();
                    $first_title = str_replace('#banjiname#', $banjiname, $this->module['config']['first_title']);
                    $first_title = str_replace('#vote#', $vote, $first_title);
                    $news[] = array(
                        'title' => $first_title,
                        'description' => '',
                        'picurl' => $this->module['config']['fengmian'],
                        'url' => $this->module['config']['third_url'],
                    );
                    $second_title = str_replace('#code#', $code, $this->module['config']['second_title']);

                    $news[] = array(
                        'title' => $second_title,
                        'description' => '',
                        'picurl' => '',
                        'url' => $this->module['config']['third_url'],
                    );

                    if (!empty($this->module['config']['third_title'])) {

                        $news[] = array(
                            'title' => $this->module['config']['third_title'],
                            'description' => '',
                            'picurl' => $this->module['config']['third_img'],
                            'url' => $this->module['config']['third_url'],
                        );

                    }
                    if (!empty($this->module['config']['fourth_title'])) {

                        $news[] = array(
                            'title' => $this->module['config']['fourth_title'],
                            'description' => '',
                            'picurl' => $this->module['config']['fourth_img'],
                            'url' => $this->module['config']['fourth_url'],
                        );

                    }

                    if (!empty($this->module['config']['fifth_title'])) {

                        $news[] = array(
                            'title' => $this->module['config']['fifth_title'],
                            'description' => '',
                            'picurl' => $this->module['config']['fifth_img'],
                            'url' => $this->module['config']['fifth_url'],
                        );

                    }
                    return $this->respNews($news);

                    // return $this->respText('你正在为'.$first_title."投票\n确认投票请回复中括号里面的验证码:[".$code."]\n5分钟回复有效orl");
                }
            } else {
                $code = $this->getrandcode();
                $cookie_user = array(//注册投票用户
                    'user' => $openid,
                    'bianhao' => $bianhao,
                    'banjiname' => $banjiname,//为哪个班级投票
                    'isvote' => 0,//是否已经投过票,0还没,1投了
                    'code' => $code,
                );
                cache_write($openid, $cookie_user);//将投票的童鞋信息写入缓存
                $this->beginContext(600);

                $news = array();

                $first_title = str_replace('#banjiname#', $banjiname, $this->module['config']['first_title']);
                $first_title = str_replace('#vote#', $vote, $first_title);
                $news[] = array(
                    'title' => $first_title,
                    'description' => '',
                    'picurl' => $this->module['config']['fengmian'],
                    'url' => $this->createMobileUrl('index'),
                );
                $second_title = str_replace('#code#', $code, $this->module['config']['second_title']);

                $news[] = array(
                    'title' => $second_title,
                    'description' => '',
                    'picurl' => '',
                    'url' => $this->createMobileUrl('index'),
                );

                if (!empty($this->module['config']['third_title'])) {

                    $news[] = array(
                        'title' => $this->module['config']['third_title'],
                        'description' => '',
                        'picurl' => $this->module['config']['third_img'],
                        'url' => $this->module['config']['third_url'],
                    );

                }
                if (!empty($this->module['config']['fourth_title'])) {

                    $news[] = array(
                        'title' => $this->module['config']['fourth_title'],
                        'description' => '',
                        'picurl' => $this->module['config']['fourth_img'],
                        'url' => $this->module['config']['fourth_url'],
                    );

                }

                if (!empty($this->module['config']['fifth_title'])) {

                    $news[] = array(
                        'title' => $this->module['config']['fifth_title'],
                        'description' => '',
                        'picurl' => $this->module['config']['fifth_img'],
                        'url' => $this->module['config']['fifth_url'],
                    );

                }
                return $this->respNews($news);
                //return $this->respText('你正在为'.$first_title."投票\n确认投票请回复中括号里面的验证码:[".$code."]\n5分钟回复有效or");
            }
        }

    }

}
    function getrandcode($length=6){//六位数的验证码
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
            //$str .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $str;
    }
}