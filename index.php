<?php
require_once __DIR__ . '/linebot_lib/linebot.php';
require_once 'phpQuery-onefile.php';

$bot = new LineBotClass();
try {
    while ($bot->check_shift_event()) { // メッセージがなくなるまでループ
        $text = $bot->get_text(); // テキストを取得
        $messeage_type = $bot->get_message_type(); // メッセージタイプを取得
        $event_type = $bot->get_event_type(); // イベントタイプを取得

        if ($event_type === "postback") { // ポストバックのイベントなら
            $bot->add_flex_builder("検索しました", createEventSearchMessage($bot->get_post_data()));
        } else if ($messeage_type === 'text') {
            $bot->add_flex_builder("エリアを選択してください", createSelectAreaMessage());
        }
        $bot->reply();
    }

} catch (Exception $e) {
    $error = $e->getMessage();
    $bot->add_text_builder("エラーキャッチ:" . $error);
    $bot->reply();
}

function createSelectAreaMessage() {
    global $bot;
    $flex_box_mein = array();
    $flex_components = array();

    $flex_components['body'][] = $bot->create_text_component("検索エリアを選択してください",array("size"=>5,"weight"=>"bold"));
  $area = ['新宿'=>'t_shinjuku', '渋谷'=>'t_shibuya', '池袋'=>'t_ikebukuro', '銀座'=>'t_ginza', '六本木'=>'t_roppongi',
          'お台場'=>'t_odaiba', '吉祥寺'=>'t_kichijoji', '上野'=>'t_ueno', '品川'=>'t_shinagawa', '浅草'=>'t_asakusa', '東京駅周辺'=>'t_tokyoeki'];
  foreach ($area as $key => $value) {
    $flex_components['body'][] = createPostActionText($key,$value);
  }

    $flex_box_mein['body'] = $bot->create_box_component("vertical",$flex_components['body'],array("spacing"=>4));

    $bubble_blocks = array(
         "body" => $flex_box_mein['body']
    );
    return [$bot->create_bubble_container($bubble_blocks)];
}

function createPostActionText($label, $data) {
   global $bot;
     $action = $bot->create_post_action_builder($label,$data,$label);
     return $bot->create_text_component($label, array("size"=>6,"wrap"=>true,"action"=>$action,"align"=>"center","color"=>"#0000ff"));
}

function createEventSearchMessage($postData) {
    $flex_bubble = array();
    $searchResult = getEventList($postData);
    foreach ($searchResult as $item) {
        $flex_bubble[] = createFlexMessage($item);
    }
    return $flex_bubble;
}

function getEventList($areaName) {
    $html = file_get_contents('https://www.walkerplus.com/event_list/today/ar0313TER/'.$areaName.'/');
    $doc = phpQuery::newDocument($html);
    $list = $doc[".m-mainlist-item"];
  return $list;
}

function createFlexMessage($item) {
    $title = pq($item)->find('a:eq(0)')->text();
    $decs = pq($item)->find('a:eq(1)')->text();
    $place = pq($item)->find('a:eq(4)')->text();
    $img = pq($item)->find('img')->attr('src');
    $link = pq($item)->find('a:eq(0)')->attr('href');

    global $bot;
    $flex_box_mein = array();
    $flex_components = array();

    $flex_components['header'][] = $bot->create_text_component($place,array("size"=>5,"weight"=>"bold","color"=>"#e60033"));
    $flex_box_mein['header'] = $bot->create_box_component("vertical",$flex_components['header'],array("spacing"=>4));

    $flex_components['body'][] = $bot->create_text_component(trim($title),array("size"=>5,"weight"=>"bold","wrap"=>true));
    $flex_components['body'][] = $bot->create_text_component($decs,array("size"=>4,"wrap"=>true));
    $flex_box_mein['body'] = $bot->create_box_component("vertical",$flex_components['body'],array("spacing"=>3));

    $action = $bot->create_url_action_builder("イベント詳細をみる",'https://www.walkerplus.com'.$link);
    $flex_components['footer'][] = $bot->create_button_component($action,array("style"=>"secondary"));
    $flex_box_mein['footer'] = $bot->create_box_component("vertical",$flex_components['footer'],array("spacing"=>3));

  $image_url = 'https:'.$img;
    $bubble_blocks = array(
         "header" => $flex_box_mein['header']
        ,"hero" => $bot->create_image_component($image_url, array("size"=>11,"aspectRatio"=>"4:3","aspectMode"=>"cover"))
        ,"body" => $flex_box_mein['body']
        ,"footer" => $flex_box_mein['footer']
    );
    return $bot->create_bubble_container($bubble_blocks);
}

?>
