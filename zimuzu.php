<?php

class API_zimuzu
{
    /*
     * author: http://weibo.com/backtrace
     */

    public $cid;
    public $accesskey;
    public $return_type = 'json';
    public $client_type = 1;

    protected $require_auth = true;

    const BASE_URL = 'http://api-domain';
    const CURL_TYPE = 'POST';

    public function __construct($cid, $accesskey, $client_type = 1, $return_type = 'json')
    {
        $this->cid = $cid;
        $this->accesskey = $accesskey;
        $this->return_type = $return_type;
        $this->client_type = $client_type;

        $allow_return_type = array('json'); // 暂未加入对jsonp、xml的支持
        $allow_client_type = array(1, 2, 3); // 客户端类型,1-IOS 2-安卓 3-WP

        if (!in_array($this->return_type, $allow_return_type)) {
            $this->return_type = 'json';
        }
        if (!in_array($this->client_type, $allow_client_type)) {
            $this->client_type = 1;
        }
    }

    /*
     * 生成验证key
     */
    protected function genAuthKey()
    {
        $timestamp = time();
        $accesskey = md5($this->cid.'$$'.$this->accesskey.'&&'.$timestamp);

        return array(
            'cid' => $this->cid,
            'accesskey' => $accesskey,
            'timestamp' => $timestamp,
            'client' => $this->client_type,
        );
    }

    /*
     * 获取网页内容
     */
    protected function connect($url, $send_data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if (self::CURL_TYPE == 'POST') {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $send_data);
        } else {
            $str = function () use ($url, $send_data) {
                $str = '';
                foreach ($send_data as $k => $v) {
                    $str .= '&'.$k.'='.url_encode($v);
                }

                return $url.$str;
            };
            $url = $str();
            curl_setopt($ch, CURLOPT_URL, $url);
        }

        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }

    protected function getResult($url, $vars = array())
    {
        $url = self::BASE_URL.$url.'?type='.$this->return_type;

        if ($this->require_auth == true) {
            $authKey = $this->genAuthKey();
        } else {
            $authKey = array();
        }

        //print_r($authKey);
        $send_data = array_merge($authKey, $vars);

        $data = $this->connect($url, $send_data);

        $this->require_auth = true;

        //var_dump($data);exit();
        $result = json_decode($data, true);
        if (is_array($result)) {
            if ($result['status'] === 1) {
                return $result['data'];
            } else {
                $error_code = $result['status'];
                $error_info = $result['info'];

                echo 'error:'.$error_code.':'.$error_info.PHP_EOL;

                return false;
            }
        } else {
            return false;
        }
    }

    /*
     * APP首页数据接口
     * 接口地址:/focus/index
     *
     * 返回数据:
     * focus_list 焦点图
     * ​title 标题
     * ​pic 焦点图地址
     * ​desc 焦点图简介
     * ​url 焦点图链接
     * top 今日前十
     * ​id 影视资源ID
     * ​cnname 影视资源中文名
     * ​channel 频道 tv-电视剧,movie-电影
     * ​area 资源地区
     * ​category​资源类型
     * ​publish_year​上映年代
     * ​play_status 播放状态
     * ​poster 海报
     * article_list 新闻资讯(第一条资讯因为是手工推荐,只有title,url,poster三个参数返回)
     * ​id 资讯ID
     * ​title 资讯标题
     * ​content 资讯内容(只截取了前100个文字)
     * ​views 浏览数
     * ​poster 海报
     * ​dateline 发布时间
     * hot_comment 热门短评
     * ​id 短评ID
     * ​author 发布者UID
     * ​channel 资源类型,movie-电影,tv-电视剧
     * ​itemid 影视资源ID
     * ​content 短评内容
     * ​good 支持数
     * ​bad 反对数
     * ​dateline 发布时间
     * ​nickname 发布者昵称
     * ​avatar 发布者头像
     * ​group_name 所属用户组
     * ​cnname 影视资源中文名
     * ​score 评分
     * ​poster 对应的资源海报图
     */
    public function getIndexData()
    {
        $path = '/focus/index';

        return $this->getResult($path);
    }

    /*
     * 影视资源列表
     * 接口地址:/resource/fetchlist
     * 传递参数:
     * channel(可选) 频道 电影:movie,电视剧:tv,公开课:openclass
     * area(可选) 国家,例如:”美国”,”日本”,”英国”
     * sort(可选) 排序 更新时间update,发布时间pubdate,上映时间premiere,名称name,排名rank,评分score,点击率views
     * year(可选) 年代 最小值为1990
     * category(可选) 影视类型 具体值请参看网站
     * limit(可选) 默认为10个,不能大于20
     * page(可选) 列表页码
     * 返回数据:
     * id 资源ID
     * cnname 中文名
     * enname 英文名
     * remark 说明
     * area 国家
     * format 格式
     * category 类型
     * poster 海报
     * channel 频道
     * lang 语言
     * play_status 播放状态
     * rank 排名
     * score 评分
     * views 浏览数
     */
    public function getFetchList(...$query)
    {
        $path = '/resource/fetchlist';

        $vars = array();

        if (!empty($query)) {
            $count = count($query);
            switch ($count) {
                case 7:
                    $vars['page'] = (int) $query[6];
                case 6:
                    $vars['limit'] = (int) $query[5];
                case 5:
                    $vars['category'] = $query[4];
                case 4:
                    $vars['year'] = (int) $query[3];
                case 3:
                    $vars['sort'] = $query[2];
                case 2:
                    $vars['area'] = $query[1];
                case 1:
                    $vars['channel'] = $query[0];
                    break;
                default:
                break;
            }
        }

        return $this->getResult($path, $vars);
    }

    /*
     * 影视资源详情
     * 接口地址:/resource/getinfo
     * 传递参数:
     * id(必选) 资源ID
     * prevue(可选) 是否获取播放档期(只有电视剧才有效) 1-获取
     * share(可选) 是否获取分享信息 1-获取
     * 返回数据:
     * cnname 中文名
     * enname 英文名
     * remark 说明
     * poster 海报
     * play_status 播放状态
     * area 地区
     * category 类型
     * views 浏览数
     * score 评分
     * content 简介
     * prevue 播放档期
     * ​season 季度
     * ​episode 集数
     * ​play_time 播放时间
     * ​week 星期
     * shareTitle 分享标题
     * shareContent 分享内容
     * shareImage 分享图片
     * shareUrl 分享地址
     * item_permission 为0表示当前用户没有权限下载资源(必须传递uid和token给当前接口),仅限IOS客户端
     */
    public function getResourceInfo($id, ...$query)
    {
        $path = '/resource/getinfo';

        $vars = array();
        $vars['id'] = (int) $id;

        if (!empty($query)) {
            $count = count($query);
            switch ($count) {
                case 2:
                    $vars['share'] = (int) $query[1];
                case 1:
                    $vars['prevue'] = (int) $query[0];
                    break;
                default:
                break;
            }
        }

        return $this->getResult($path, $vars);
    }

    /*
     * 影视资源季度信息
     * 接口地址:/resource/season_episode
     * 传递参数:
     * id(必选) 影视ID
     * 返回数据:
     * season 季度
     * episode 集数
     * 该接口会把电视剧的所有季度信息列出来(包括了单剧等),如果影视是电影则返回错误信息
     * 例如:{‘season’:7,’episode’:10} 表示第7季总共有10集
     */
    public function getSeasonEpisode($id)
    {
        $path = '/resource/season_episode';

        $vars['id'] = (int) $id;

        return $this->getResult($path, $vars);
    }

    /*
     * 影视下载资源列表
     * 接口地址:/resource/itemlist
     * 传递参数:
     * id(必选) 影视ID
     * client(必选) 客户端类型,1-IOS,2-安卓,3-WP
     * uid(必选) 用户UID
     * token(必选) 用户token
     * file(可选) 是否同时获取下载链接 1-获取,0-不获取
     * click(可选) 部分app客户端默认只输出固定的中文字幕,更多的需要再次点击获得,click为1则表示获取更多的数据

     * 返回数据(电视剧的数组结构,第一层是季度信息,第二层是格式,第三层是数据列表,电影和公开课的第一层是资源格式,第二层才是数据列表):
     * id 资源ID
     * name 资源名
     * format 资源格式
     * season 资源季度
     * episode 资源集数
     * size 文件大小
     * dateline 资源添加时间
     * link 当需要同时获取下载链接时该参数有数据,仅限返回电驴和磁力链接
     * info 如果当前用户没有足够权限获取电视剧的资源列表,该参数会输出提示用户最多只能查看资源条数的信息,默认为空
     */
    public function getItemList($id, $client, $uid, $token, ...$query)
    {
        $path = '/resource/itemlist';

        $vars = array();
        $this->client_type = (int) $client;
        $vars['id'] = (int) $id;
        $vars['uid'] = $uid;
        $vars['token'] = $token;

        if (!empty($query)) {
            $count = count($query);
            switch ($count) {
                case 2:
                    $vars['click'] = $query[1];
                case 1:
                    $vars['file'] = $query[0];
                    break;
                default:
                break;
            }
        }

        return $this->getResult($path, $vars);
    }

    /*
     * 影视下载资源列表(无需验证用户权限)
     * 接口地址:/resource/itemlist_web
     * 传递参数:
     * id(必选) 影视ID
     * season(必选) 资源季度
     * episode(必选) 资源集数
     * file(可选) 是否同时获取下载链接 1-获取,0-不获取
     * 返回数据
     * id 资源ID
     * name 资源名
     * format 资源格式
     * season 资源季度
     * episode 资源集数
     * size 文件大小
     * dateline 资源添加时间
     * link 当需要同时获取下载链接时该参数有数据,仅限返回电驴和磁力链接
     * info 如果当前用户没有足够权限获取电视剧的资源列表,该参数会输出提示用户最多只能查看资源条数的信息,默认为空
     */
    public function getItemListNoAuth($id, $season, $episode, $file = 1)
    {
        $path = '/resource/itemlist_web';

        $vars = array();
        $vars['id'] = (int) $id;
        $vars['season'] = $season;
        $vars['episode'] = $episode;
        $vars['file'] = $file;

        return $this->getResult($path, $vars);
    }

    /*
     * 影视资源下载地址
     * 接口地址:/resource/itemlink
     * 传递参数:
     * id(必选) 资源ID
     * 返回参数:
     * address 下载地址
     * way 下载方式 1-电驴  2-磁力   9-网盘    12-城通盘
     */
    public function getItemLink($id)
    {
        $path = '/resource/itemlink';

        $vars = array();
        $vars['id'] = (int) $id;

        return $this->getResult($path, $vars);
    }

    /*
     * 字幕列表
     * 接口地址:/subtitle/fetchlist
     * 传递参数:
     * limit(可选) 数量
     * page(可选) 页码
     * 返回数据:
     * count 字幕总数
     * list 字幕集合
     * id 字幕ID
     * cnname 字幕中文名
     * enname 字幕英文名
     * resourceid 对应的资源ID
     * resource_info 资源详情
     * ​cnname 中文名
     * ​enname 英文名
     * ​poster 海报
     * segment 对应片源
     * source 字幕发布者 zimuzu(字幕组)
     * category 类型
     * lang 语言
     * format 格式
     * remark 备注
     * views 浏览数
     * dateline 发布时间
     */
    public function getSubtitleList(...$query)
    {
        $path = '/subtitle/fetchlist';

        $vars = array();

        if (!empty($query)) {
            $count = count($query);
            switch ($count) {
                case 2:
                    $vars['page'] = (int) $query[1];
                case 1:
                    $vars['limit'] = (int) $query[0];
                    break;
                default:
                break;
            }
        }

        return $this->getResult($path, $vars);
    }

    /*
     * 字幕详情
     * 接口地址:/subtitle/getinfo
     * 传递参数:
     * id 字幕ID
     * 返回数据:
     * id 字幕ID
     * cnname 字幕中文名
     * enname 字幕英文名
     * resourceid 对应的资源ID
     * segment 对应片源
     * source 字幕发布者 zimuzu(字幕组)
     * category 类型
     * file 字幕文件下载地址(如果用户没权限浏览则为空)
     * filename 字幕文件名
     * lang 语言
     * format 格式
     * remark 备注
     * views 浏览数
     * dateline 发布时间
     * protect_expire 字幕下载保护期到期时间(unix时间戳),表示当前字幕处于保护期内,用户不能查看,同时file的值为空,如为0则表示没有保护期或者已过期
     * resource_info 对应的资源信息
     * ​cnname 中文名
     * ​enname 英文名
     * ​poster 海报
     */
    public function getSubtitleInfo($id)
    {
        $path = '/subtitle/getinfo';

        $vars = array();
        $vars['id'] = (int) $id;

        return $this->getResult($path, $vars);
    }
	
    /*
     * 字幕详情(无需验证用户权限)
     * 接口地址:/subtitle/getinfo_web
     * 传递参数:
     * id 字幕ID
     * 返回数据:
     * id 字幕ID
     * cnname 字幕中文名
     * enname 字幕英文名
     * resourceid 对应的资源ID
     * segment 对应片源
     * source 字幕发布者 zimuzu(字幕组)
     * category 类型
     * file 字幕文件下载地址(如果用户没权限浏览则为空)
     * filename 字幕文件名
     * lang 语言
     * format 格式
     * remark 备注
     * views 浏览数
     * dateline 发布时间
     * protect_expire 字幕下载保护期到期时间(unix时间戳),表示当前字幕处于保护期内,用户不能查看,同时file的值为空,如为0则表示没有保护期或者已过期
     * resource_info 对应的资源信息
     * ​cnname 中文名
     * ​enname 英文名
     * ​poster 海报
     */
    public function getSubtitleInfoNoAuth($id)
    {
        $path = '/subtitle/getinfo_web';

        $vars = array();
        $vars['id'] = (int) $id;

        return $this->getResult($path, $vars);
    }

    /*
     * 资讯列表
     * 接口地址:/article/fetchlist
     * 传递参数:
     * newstype news-新闻,report-收视快报,m_review-影评,t_review-剧评,new_review-新剧评测,recom-片单推荐 默认为所有类型
     * limit(可选) 数量
     * page(可选) 页码
     * 返回数据:
     * ID 资讯ID
     * Title 资讯标题
     * Type 资讯类型 news-新闻,guide-导视,影评-movie_review,剧评-tv_review
     * Poster 海报
     * Dateline​发布时间
     */
    public function getArticleList(...$query)
    {
        $path = '/article/fetchlist';

        $vars = array();

        if (!empty($query)) {
            $count = count($query);
            switch ($count) {
                case 3:
                    $vars['page'] = (int) $query[2];
                case 2:
                    $vars['limit'] = (int) $query[1];
                case 1:
                    $vars['newstype'] = $query[0];
                    break;
                default:
                break;
            }
        }

        return $this->getResult($path, $vars);
    }

    /*
     * 资讯内容
     * 接口地址:/article/getinfo
     * 传递参数:
     * id 资讯ID
     * 返回数据:
     * id 资讯ID
     * title 资讯标题
     * content 资讯内容
     * dateline 发布时间
     * poster 海报
     * resourceid 对应的影视资源ID,可能为0,表示没有关联影视资源
     */
    public function getArticleInfo($id)
    {
        $path = '/article/getinfo';

        $vars = array();
        $vars['id'] = $id;

        return $this->getResult($path, $vars);
    }

    /*
     * 全站搜索
     * 接口地址: /search
     * 传递参数:
     * k(必选) 搜索关键词
     * st(可选) 搜索类型,resource-影视资源,subtitle-字幕资源,article-资讯以及影评和剧评.如果为空,则在以上三种资源中搜索
     * order(可选) 排序 pubtime发布时间 uptime更新时间    默认为更新时间
     * limit(可选) 每页数量(默认输出20个)
     * page(可选) 页码
     * 返回数据:
     * itemid 对应的资源ID
     * title 资源标题
     * type resource-影视资源 subtitle-字幕 article-资讯
     * channel 当type为resource的时候有效,tv-电视剧,movie-电影,openclass-公开课
     * pubtime 发布时间
     * uptime 更新时间
     */
    public function searchItem($keyword, ...$query)
    {
        $path = '/search';

        $vars = array();
        $vars['k'] = $keyword;

        if (!empty($query)) {
            $count = count($query);
            switch ($count) {
                case 4:
                    $vars['page'] = (int) $query[3];
                case 3:
                    $vars['limit'] = (int) $query[2];
                case 2:
                    $vars['order'] = $query[1];
                case 1:
                    $vars['st'] = $query[0];
                    break;
                default:
                break;
            }
        }

        return $this->getResult($path, $vars);
    }

    /*
     * 美剧时间表
     * 接口地址:/tv/schedule
     * 传递参数:
     * start(必选) 开始时间,标准的时间格式,如:2015-02-03或2015-2-3或20150203
     * end(必选) 结束时间,同上,开始时间和结束时间不能超过31天
     * limit(可选) 返回数量
     * 返回数据:
     * id 电视剧ID
     * cnname 电视剧中文名
     * enname 电视剧英文名
     * season 季度
     * episode 集数
     * poster 海报
     */
    public function getTVSchedule($start, $end, ...$query)
    {
        $path = '/tv/schedule';

        $vars = array();
        $vars['start'] = $start;
        $vars['end'] = $end;

        if (!empty($query)) {
            $count = count($query);
            switch ($count) {
                case 1:
                    $vars['limit'] = (int) $query[0];
                    break;
                default:
                break;
            }
        }

        return $this->getResult($path, $vars);
    }

    /*
     * 今日热门排行
     * 接口地址:/resource/top
     * 传递参数:
     * channel(可选) 频道 默认为电影和电视剧的排行榜  tv电视剧 movie 电影
     * limit(可选) 获取数量,默认为5个
     * 返回数据:
     * id 影视ID
     * cnname 中文名
     * channel 频道
     * area 国家
     * category 类型
     * publish_year 发布年份
     * play_status 播放状态
     */
    public function getTodayTop(...$query)
    {
        $path = '/resource/top';

        $vars = array();

        if (!empty($query)) {
            $count = count($query);
            switch ($count) {
                case 2:
                    $vars['limit'] = (int) $query[1];
                case 1:
                    $vars['channel'] = $query[0];
                    break;
                default:
                break;
            }
        }

        return $this->getResult($path, $vars);
    }

    /*
     * 今日更新
     * 接口地址:/resource/today
     * 返回数据:
     * resourceid 影视ID
     * name 下载资源名
     * format 格式
     * season 季度
     * episode 集数
     * size 文件大小
     * ways 下载方式集合   1-电驴 2-磁力
     */
    public function getTodayUpdate()
    {
        $path = '/resource/today';

        return $this->getResult($path);
    }

    /*
     * 签到状态
     * 接口地址:/user/sign_status
     * 传递参数:
     * uid 用户ID
     * token 登录凭证
     * 返回数据:
     * group_name 用户组
     * need_day 升级所需天数
     * last_sign 最近三次登录时间
     * sign_times 连续签到天数
     */
    public function getUserSignStatus($uid, $token)
    {
        $path = '/user/sign_status';

        $vars = array();
        $vars['uid'] = $uid;
        $vars['token'] = $token;

        return $this->getResult($path, $vars);
    }

    /*
     * 用户签到
     * 接口地址:/user/sign
     * 传递参数:
     * uid 用户ID
     * token 登录凭证
     * 返回数据:
     * 签到成功status返回1,info是签到成功的提示语
     * group_name 用户组
     * need_day 升级所需天数
     * last_sign 最近三次登录时间
     * sign_times 连续签到天数
     */
    public function doUserSign($uid, $token)
    {
        $path = '/user/sign';

        $vars = array();
        $vars['uid'] = $uid;
        $vars['token'] = $token;

        return $this->getResult($path, $vars);
    }

    /*
     * 获取收藏列表
     * 接口地址:/fav/fetchlist
     * 传递参数:
     * uid 用户ID
     * token 登录凭证
     * ft 收藏类型 tv-电视剧,movie-电影,openclass-公开课 默认为空
     * page 页码
     * limit 每页数量
     * 返回数据:
     * count:收藏总数
     * list 收藏列表
     * itemid 资源ID
     * poster 资源海报
     * channel 资源类型tv,movie,openclass
     * area 资源地区
     * cnname 资源中文名
     * enname 资源英文名
     * category 资源类型
     * publish_year​发布年代
     * remark 说明
     * play_status 播放状态
     * premiere 首播日期
     * updatetime 更新时间
     * prevue 播放时间表,可能为空
     */

    /*
     * 找回密码
     * 接口地址:/user/forget
     * 传递参数:
     * email 邮箱账号
     * 返回数据:
     * status-返回状态,info-提示信息,操作成功后回提示用户去邮箱查看找回密码的链接
     */
    public function resetPassword($email)
    {
        $path = '/user/forget';

        $vars = array();
        $vars['email'] = $email;

        return $this->getResult($path, $vars);
    }

    /*
     * 收藏状态
     * 接口地址:/fav/check_follow
     * 传递参数:
     * id 影视资源ID
     * 返回数据:
     * data 1-已收藏 0-未收藏
     */
    public function favCheckFollow($id)
    {
        $path = '/fav/check_follow';

        $vars = array();
        $vars['id'] = $id;

        return $this->getResult($path, $vars);
    }

    /*
     * 收藏资源
     * 接口地址:/fav/follow
     * 传递参数:
     * id 资源ID
     * 返回数据:
     * status为1则表示操作成功
     */
    public function favFollow($id)
    {
        $path = '/fav/follow';

        $vars = array();
        $vars['id'] = $id;

        return $this->getResult($path, $vars);
    }

    /*
     * 取消收藏
     * 接口地址:/fav/unfollow
     * 传递参数:
     * id 资源ID
     * 返回数据:
     * status为1则表示操作成功
     */
    public function favUnfollow($id)
    {
        $path = '/fav/unfollow';

        $vars = array();
        $vars['id'] = $id;

        return $this->getResult($path, $vars);
    }

     /*
     * 获取短评
     * 短评接口是全站短评通用的接口,不再对影视或字幕等做单独的接口.无论当前用户是否登录,调用短评接口都需要uid和token两个参数
     * 返回的参数中status为1,则表示返回正常,否则会返回失败原因,其他短评接口相同
     * 接口地址:/comment/fetch
     * 传递参数:
     * channel 频道,article-资讯,openclass-公开课,tv-电视剧,movie-电影,subtitle-字幕
     * itemid 对应的资源ID
     * pagesize 每页数量
     * page(可选) 页码,默认为最后一页
     * 获取数据:
     * count 短评总数
     * pageCount 总页码数
     * page 当前页数
     * pagesize 每页短评数
     * list 短评数组
     * ​id 短评ID
     * ​author 发布人UID
     * ​nickname 发布人昵称
     * ​avatar 发布人头像
     * ​content 短评内容
     * ​good 支持数
     * ​bad 反对数
     * ​dateline 短评发布时间
     * ​hot 1-热门短评,只有在page为第一页,最后一页或者未输入值的时候才有数据
     * ​reply 该短评的回复评论,返回的参数与上面类似
     * ​avatar 头像
     * ​group_name 所属用户组
     */
    public function getComment($channel, $itemid, $pagesize, ...$query)
    {
        $path = '/comment/fetch';

        $vars = array();
        $vars['channel'] = $channel;
        $vars['itemid'] = $itemid;
        $vars['pagesize'] = $pagesize;

        if (!empty($query)) {
            $count = count($query);
            switch ($count) {
                case 1:
                    $vars['page'] = $query[0];
                    break;
                default:
                break;
            }
        }

        return $this->getResult($path, $vars);
    }

    /*
     * 保存短评
     * 接口地址:/comment/save
     * 传递参数:
     * channel 频道,article-资讯,openclass-公开课,tv-电视剧,movie-电影,subtitle-字幕
     * itemid 对应的资源ID
     * content 短评内容
     * replyid 如果是回复短评,则为对应的短评ID,否则为0
     */
    public function saveComment($channel, $itemid, $content, $replyid = 0)
    {
        $path = '/comment/save';

        $vars = array();
        $vars['channel'] = $channel;
        $vars['itemid'] = $itemid;
        $vars['content'] = $content;
        $vars['replyid'] = (int) $replyid;

        return $this->getResult($path, $vars);
    }

    /*
     * 更新短评
     * 接口地址:/comment/update
     * 传递参数:
     * commentId 短评ID
     * content 短评内容
     */
    public function updateComment($commentId, $content)
    {
        $path = '/comment/update';

        $vars = array();
        $vars['commentId'] = $commentId;
        $vars['content'] = $content;

        return $this->getResult($path, $vars);
    }

    /*
     * 删除短评
     * 接口地址:/comment/delete
     * 传递参数:
     * id 短评ID
     */
    public function deleteComment($commentId)
    {
        $path = '/comment/delete';

        $vars = array();
        $vars['id'] = $commentId;

        return $this->getResult($path, $vars);
    }

    /*
     * 支持短评
     * 接口地址:/comment/good
     * 传递参数:
     * id 短评ID
     */
    public function goodComment($commentId)
    {
        $path = '/comment/good';

        $vars = array();
        $vars['id'] = $commentId;

        return $this->getResult($path, $vars);
    }

    /*
     * 反对短评
     * 接口地址:/comment/bad
     * 传递参数:
     * id 短评ID
     * 网站配置
     */
    public function badComment($commentId)
    {
        $path = '/comment/bad';

        $vars = array();
        $vars['id'] = $commentId;

        return $this->getResult($path, $vars);
    }

    /*
     * 以下接口都不需要权限验证,可以直接访问获取
     */

    /*
     * 网站全局参数
     * 接口地址:/config/app
     */
    public function getConfigApp()
    {
        $path = '/config/app';
        $this->require_auth = false;

        return $this->getResult($path);
    }

    /*
     * 资源类型
     * 接口地址:/config/resource_category
     */
    public function getConfigResourceCategory()
    {
        $path = '/config/resource_category';
        $this->require_auth = false;

        return $this->getResult($path);
    }

    /*
     * 资源地区
     * 接口地址:/config/resource_area
     */
    public function getConfigResourceArea()
    {
        $path = '/config/resource_area';
        $this->require_auth = false;

        return $this->getResult($path);
    }

    /*
     * 资源格式
     * 接口地址:/config/resource_format
     */
    public function getConfigResourceFormat()
    {
        $path = '/config/resource_format';
        $this->require_auth = false;

        return $this->getResult($path);
    }

    /*
     * 资源语言
     * 接口地址:/config/resource_lang
     */
    public function getConfigResourceLang()
    {
        $path = '/config/resource_lang';
        $this->require_auth = false;

        return $this->getResult($path);
    }

    /*
     * 资源电视台
     * 接口地址:/config/resource_tv
     */
    public function getConfigResourceTV()
    {
        $path = '/config/resource_tv';
        $this->require_auth = false;

        return $this->getResult($path);
    }

    /*
     * 广告内容
     * 接口地址:/ad
     * 返回数据:
     * index 首页
     * resource_list 影视资源列表
     * resource_show 影视资源详情页
     * resource_file_show 影视资源文件详情
     * schedule 时间表
     * subitlte_show 字幕详情页
     * sign 签到页
     * fav 我的收藏
     * 以上每个参数又对应了link和pic两个,分别为广告链接和广告图片
     */
    public function getAd()
    {
        $path = '/ad';
        $this->require_auth = false;

        return $this->getResult($path);
    }

    /*
     * 版本检查
     * 接口地址:/version/check
     * 传递参数:
     * vcode:版本号,使用整形数字
     * 返回数据:
     * need_update:是否需要更新 true-需要,false-不需要
     * download_url:下载地址
     * version:最新版本号
     * content:更新信息
     */
    public function versionCheck($vcode)
    {
        $path = '/version/check';
        $this->require_auth = false;

        $vars = array();
        $vars['vcode'] = $vcode;

        return $this->getResult($path, $vars);
    }
}
