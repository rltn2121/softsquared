<?php

// 1 찜한 음식점 개수, 정보
function getFavoriteCount($user_id)
{
    $pdo = pdoSqlConnect();
    $query = "select count(*) as total_count
                from favorite_restaurant
                where user_id=?
                and status=1;";
    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['total_count']);
}
function getFavoriteRestaurant($user_id)
{
    $pdo = pdoSqlConnect();
    $query = "select restaurant_name,
       restaurant.restaurant_id,
       ifnull(round(avg((taste_score + quantity_score + delivery_score) / 3), 1),0) as score,
       count(review_id)                                                   as review_num,
       count(review_owner_comment.contents)                               as owner_comment_num,
       concat(restaurant.delivery_discount, '원')                          as delivery_discount,
       best_menu,
       is_deliver,
       image_url
from (favorite_restaurant
    left outer join (restaurant left outer join (orders left outer join (review left outer join review_owner_comment using (review_id)) using (order_id)) using (restaurant_id))
    using (restaurant_id))
         left outer join (select restaurant_id, group_concat(temp.menu_name order by temp.sales desc) as best_menu
                          from (select orders.restaurant_id, menu_name, sum(quantity) as sales
                                from orders
                                         left outer join (ordered_menu left outer join menu using (menu_id)) using (order_id)
                                group by restaurant_id, menu_name) as temp
                          group by restaurant_id) as t using (restaurant_id)
where favorite_restaurant.user_id = ?
  and status = 1
group by favorite_restaurant.restaurant_id;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

// 2.1. 찜한 식당 목록에 존재 여부
function isFavoriteExist($user_id,$rest_id)
{
    $pdo = pdoSqlConnect();
    // user_id, rest_id 존재하는지 확인
    $query = "SELECT EXISTS(select * FROM favorite_restaurant WHERE user_id = ? and restaurant_id = ?) as exist;";
    $st = $pdo->prepare($query);
    $st->execute([$user_id,$rest_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}
// 2.2. 찜한 식당 추가
function addFavorite($user_id,$rest_id)
{
    $pdo = pdoSqlConnect();
    $query="insert into favorite_restaurant (user_id, restaurant_id) values (?, ?);";
    $st = $pdo->prepare($query);
    $st->execute([$user_id,$rest_id]);

    $st = null;
    $pdo = null;
}
// 2.3. 찜 상태 확인 / 변경
function getFavoriteStatus($user_id,$rest_id)
{
    $pdo = pdoSqlConnect();
    $query="select status
from favorite_restaurant
where user_id=? and restaurant_id=?;";
    $st = $pdo->prepare($query);
    $st->execute([$user_id,$rest_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['status']);
}
function updateFavoriteToTrue($user_id,$rest_id)
{
    $pdo = pdoSqlConnect();
    $query="update favorite_restaurant
            set status=1
            where user_id = ?
              and restaurant_id = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id,$rest_id]);

    $st = null;
    $pdo = null;
}
function updateFavoriteToFalse($user_id,$rest_id)
{
    $pdo = pdoSqlConnect();
    $query="update favorite_restaurant
            set status=0
            where user_id = ?
              and restaurant_id = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id,$rest_id]);

    $st = null;
    $pdo = null;
}

// 3. 우리동네 플러스, 슈퍼레드위크, 일반음식점 (회원용)

//function restaurantTest($category, $user_id)
//{
//    $category_filter="";
//    switch($category){
//        case "전체보기":
//            $category_filter=';';
//            break;
//        case "1인분주문":
//            $category_filter= " and is_portion = 'Y';";
//            break;
//        case "야식":
//            $category_filter= " and is_night = 'Y';";
//            break;
//        case "프랜차이즈":
//            $category_filter= " and is_franchise = 'Y';";
//            break;
//        case "요기요플러스":
//            $category_filter= " and is_yogiyo_plus = 'Y';";
//            break;
//        case "치킨":
//            $category_filter= " and restaurant.type = '치킨';";
//            break;
//        case "중국집":
//            $category_filter= " and restaurant.type = '중국집';";
//            break;
//        case "피자/양식":
//            $category_filter= " and restaurant.type = '피자/양식';";
//            break;
//        case "한식":
//            $category_filter= " and restaurant.type = '한식';";
//            break;
//        case "분식":
//            $category_filter= " and restaurant.type = '분식';";
//            break;
//        case "카페/디저트":
//            $category_filter= " and restaurant.type = '카페/디저트';";
//            break;
//        case "족발/보쌈":
//            $category_filter= " and restaurant.type = '족발/보쌈';";
//            break;
//        case "편의점/마트":
//            $category_filter= " and restaurant.type = '편의점/마트';";
//            break;
//    }
//    $pdo = pdoSqlConnect();
//    // 우리 동네 플러스
//    $query = "select restaurant_name,
//       restaurant_id,
//       restaurant.region,
//       score,
//       review_num,
//       t.owner_comment_num,
//       concat(delivery_discount,'원') as delivery_discount,
//       discount_rate,
//       is_best_restaurant,
//       is_cesco,
//       datediff(now(), created_at) as sales_days,
//       estimated_delivery_time,
//       image_url,
//       is_deliver
//from restaurant
//         left outer join (
//    select restaurant_id,
//           round(avg((taste_score + quantity_score + delivery_score) / 3),1) as score,
//           count(*)                                                 as review_num,
//           count(review_owner_comment.contents)                     as owner_comment_num
//    from (review
//        left outer join orders using (order_id))
//             left outer join review_owner_comment using (review_id)
//    group by restaurant_id
//) as t using (restaurant_id)
//where restaurant.region = (select region from users where user_id = ?)";
//
//    $our_village_plus_filter = " and restaurant.our_village_plus = 'Y'";
//    $super_red_week_filter = " and restaurant.super_red_week = 'Y'";
//    $normal_restaurant_filter = " and restaurant.our_village_plus = 'N' and restaurant.super_red_week = 'Y'";
//
//    $st = $pdo->prepare($query.$our_village_plus_filter.$category_filter);
//    $st->execute([$user_id]);
//    $st->setFetchMode(PDO::FETCH_ASSOC);
//    $our_village_plus = $st->fetchAll();
//
//    $st = $pdo->prepare($query.$super_red_week_filter.$category_filter);
//    $st->execute([$user_id]);
//    $st->setFetchMode(PDO::FETCH_ASSOC);
//    $super_red_week = $st->fetchAll();
//
//    $st = $pdo->prepare($query.$normal_restaurant_filter.$category_filter);
//    $st->execute([$user_id]);
//    $st->setFetchMode(PDO::FETCH_ASSOC);
//    $normal_restaurant = $st->fetchAll();
//    $st = null;
//    $pdo = null;
//
//    $res = array($our_village_plus, $super_red_week, $normal_restaurant);
//    return $res;
//}
function getOurVillagePlusByCategory($category, $user_id)
{
    $where_clause="";
    switch($category){
        case "전체보기":
            $where_clause='';
            break;
        case "1인분주문":
            $where_clause= " and is_portion = 'Y'";
            break;
        case "야식":
            $where_clause= " and is_night = 'Y'";
            break;
        case "프랜차이즈":
            $where_clause= " and is_franchise = 'Y'";
            break;
        case "요기요플러스":
            $where_clause= " and is_yogiyo_plus = 'Y'";
            break;
        case "치킨":
            $where_clause= " and restaurant.type = '치킨'";
            break;
        case "중국집":
            $where_clause= " and restaurant.type = '중국집'";
            break;
        case "피자/양식":
            $where_clause= " and restaurant.type = '피자/양식'";
            break;
        case "한식":
            $where_clause= " and restaurant.type = '한식'";
            break;
        case "분식":
            $where_clause= " and restaurant.type = '분식'";
            break;
        case "카페/디저트":
            $where_clause= " and restaurant.type = '카페/디저트'";
            break;
        case "족발/보쌈":
            $where_clause= " and restaurant.type = '족발/보쌈'";
            break;
        case "편의점/마트":
            $where_clause= " and restaurant.type = '편의점/마트'";
            break;
    }
    $pdo = pdoSqlConnect();
    // 우리 동네 플러스
    $query = "select restaurant_name,
       restaurant_id,
       restaurant.region,
       ifnull(round(avg((taste_score + quantity_score + delivery_score) / 3), 1),0) as score,
       count(review_id)                                                   as review_num,
       count(review_owner_comment.contents)                               as owner_comment_num,
       concat(restaurant.delivery_discount, '원')                          as delivery_discount,
       best_menu,
       discount_rate,
       is_best_restaurant,
       is_cesco,
       datediff(now(), restaurant.created_at) as sales_days,
       estimated_delivery_time,
       image_url,
       is_deliver
from
    (restaurant left outer join (orders left outer join (review left outer join review_owner_comment using (review_id)) using (order_id)) using (restaurant_id))
         left outer join (select restaurant_id, group_concat(temp.menu_name order by temp.sales desc) as best_menu
                          from (select orders.restaurant_id, menu_name, sum(quantity) as sales
                                from orders
                                         left outer join (ordered_menu left outer join menu using (menu_id)) using (order_id)
                                group by restaurant_id, menu_name) as temp
                          group by restaurant_id) as t using (restaurant_id)
where restaurant.our_village_plus = 'Y'
  and restaurant.region = (select region from users where user_id = ?)
".$where_clause."group by restaurant.restaurant_id;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
function getSuperRedWeekPlusByCategory($category, $user_id)
{
    $where_clause="";
    switch($category){
        case "전체보기":
            $where_clause='';
            break;
        case "1인분주문":
            $where_clause= " and is_portion = 'Y'";
            break;
        case "야식":
            $where_clause= " and is_night = 'Y'";
            break;
        case "프랜차이즈":
            $where_clause= " and is_franchise = 'Y'";
            break;
        case "요기요플러스":
            $where_clause= " and is_yogiyo_plus = 'Y'";
            break;
        case "치킨":
            $where_clause= " and restaurant.type = '치킨'";
            break;
        case "중국집":
            $where_clause= " and restaurant.type = '중국집'";
            break;
        case "피자/양식":
            $where_clause= " and restaurant.type = '피자/양식'";
            break;
        case "한식":
            $where_clause= " and restaurant.type = '한식'";
            break;
        case "분식":
            $where_clause= " and restaurant.type = '분식'";
            break;
        case "카페/디저트":
            $where_clause= " and restaurant.type = '카페/디저트'";
            break;
        case "족발/보쌈":
            $where_clause= " and restaurant.type = '족발/보쌈'";
            break;
        case "편의점/마트":
            $where_clause= " and restaurant.type = '편의점/마트'";
            break;
    }
    $pdo = pdoSqlConnect();
    $query = "select restaurant_name,
       restaurant_id,
       restaurant.region,
       ifnull(round(avg((taste_score + quantity_score + delivery_score) / 3), 1),0) as score,
       count(review_id)                                                   as review_num,
       count(review_owner_comment.contents)                               as owner_comment_num,
       concat(restaurant.delivery_discount, '원')                          as delivery_discount,
       best_menu,
       discount_rate,
       is_best_restaurant,
       is_cesco,
       datediff(now(), restaurant.created_at) as sales_days,
       estimated_delivery_time,
       image_url,
       is_deliver
from
    (restaurant left outer join (orders left outer join (review left outer join review_owner_comment using (review_id)) using (order_id)) using (restaurant_id))
         left outer join (select restaurant_id, group_concat(temp.menu_name order by temp.sales desc) as best_menu
                          from (select orders.restaurant_id, menu_name, sum(quantity) as sales
                                from orders
                                         left outer join (ordered_menu left outer join menu using (menu_id)) using (order_id)
                                group by restaurant_id, menu_name) as temp
                          group by restaurant_id) as t using (restaurant_id)
where restaurant.super_red_week = 'Y'
  and restaurant.region = (select region from users where user_id = ?)
".$where_clause."group by restaurant.restaurant_id;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
function getNormalRestaurantByCategory($category, $user_id)
{
    $where_clause="";
    switch($category){
        case "전체보기":
            $where_clause='';
            break;
        case "1인분주문":
            $where_clause= " and is_portion = 'Y'";
            break;
        case "야식":
            $where_clause= " and is_night = 'Y'";
            break;
        case "프랜차이즈":
            $where_clause= " and is_franchise = 'Y'";
            break;
        case "요기요플러스":
            $where_clause= " and is_yogiyo_plus = 'Y'";
            break;
        case "치킨":
            $where_clause= " and restaurant.type = '치킨'";
            break;
        case "중국집":
            $where_clause= " and restaurant.type = '중국집'";
            break;
        case "피자/양식":
            $where_clause= " and restaurant.type = '피자/양식'";
            break;
        case "한식":
            $where_clause= " and restaurant.type = '한식'";
            break;
        case "분식":
            $where_clause= " and restaurant.type = '분식'";
            break;
        case "카페/디저트":
            $where_clause= " and restaurant.type = '카페/디저트'";
            break;
        case "족발/보쌈":
            $where_clause= " and restaurant.type = '족발/보쌈'";
            break;
        case "편의점/마트":
            $where_clause= " and restaurant.type = '편의점/마트'";
            break;
    }
    $pdo = pdoSqlConnect();
    $query = "select restaurant_name,
       restaurant_id,
       restaurant.region,
       ifnull(round(avg((taste_score + quantity_score + delivery_score) / 3), 1),0) as score,
       count(review_id)                                                   as review_num,
       count(review_owner_comment.contents)                               as owner_comment_num,
       concat(restaurant.delivery_discount, '원')                          as delivery_discount,
       best_menu,
       discount_rate,
       is_best_restaurant,
       is_cesco,
       datediff(now(), restaurant.created_at) as sales_days,
       estimated_delivery_time,
       image_url,
       is_deliver
from
    (restaurant left outer join (orders left outer join (review left outer join review_owner_comment using (review_id)) using (order_id)) using (restaurant_id))
         left outer join (select restaurant_id, group_concat(temp.menu_name order by temp.sales desc) as best_menu
                          from (select orders.restaurant_id, menu_name, sum(quantity) as sales
                                from orders
                                         left outer join (ordered_menu left outer join menu using (menu_id)) using (order_id)
                                group by restaurant_id, menu_name) as temp
                          group by restaurant_id) as t using (restaurant_id)
where restaurant.our_village_plus = 'N'
  and restaurant.super_red_week = 'N'
  and restaurant.region = (select region from users where user_id = ?)
".$where_clause."group by restaurant.restaurant_id;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

// 3. 우리동네 플러스, 슈퍼레드위크, 일반음식점 (비회원용)

function getOurVillagePlusByCategoryForNonmember($category, $region)
{
    $where_clause="";
    switch($category){
        case "전체보기":
            $where_clause='';
            break;
        case "1인분주문":
            $where_clause= " and is_portion = 'Y'";
            break;
        case "야식":
            $where_clause= " and is_night = 'Y'";
            break;
        case "프랜차이즈":
            $where_clause= " and is_franchise = 'Y'";
            break;
        case "요기요플러스":
            $where_clause= " and is_yogiyo_plus = 'Y'";
            break;
        case "치킨":
            $where_clause= " and restaurant.type = '치킨'";
            break;
        case "중국집":
            $where_clause= " and restaurant.type = '중국집'";
            break;
        case "피자/양식":
            $where_clause= " and restaurant.type = '피자/양식'";
            break;
        case "한식":
            $where_clause= " and restaurant.type = '한식'";
            break;
        case "분식":
            $where_clause= " and restaurant.type = '분식'";
            break;
        case "카페/디저트":
            $where_clause= " and restaurant.type = '카페/디저트'";
            break;
        case "족발/보쌈":
            $where_clause= " and restaurant.type = '족발/보쌈'";
            break;
        case "편의점/마트":
            $where_clause= " and restaurant.type = '편의점/마트'";
            break;
    }
    $pdo = pdoSqlConnect();
    // 우리 동네 플러스
    $query = "select restaurant_name,
       restaurant_id,
       restaurant.region,
       ifnull(round(avg((taste_score + quantity_score + delivery_score) / 3), 1),0) as score,
       count(review_id)                                                   as review_num,
       count(review_owner_comment.contents)                               as owner_comment_num,
       concat(restaurant.delivery_discount, '원')                          as delivery_discount,
       best_menu,
       discount_rate,
       is_best_restaurant,
       is_cesco,
       datediff(now(), restaurant.created_at) as sales_days,
       estimated_delivery_time,
       image_url,
       is_deliver
from
    (restaurant left outer join (orders left outer join (review left outer join review_owner_comment using (review_id)) using (order_id)) using (restaurant_id))
         left outer join (select restaurant_id, group_concat(temp.menu_name order by temp.sales desc) as best_menu
                          from (select orders.restaurant_id, menu_name, sum(quantity) as sales
                                from orders
                                         left outer join (ordered_menu left outer join menu using (menu_id)) using (order_id)
                                group by restaurant_id, menu_name) as temp
                          group by restaurant_id) as t using (restaurant_id)
where restaurant.our_village_plus = 'Y'
  and restaurant.region = ?
".$where_clause."group by restaurant.restaurant_id;";

    $st = $pdo->prepare($query);
    $st->execute([$region]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
function getSuperRedWeekPlusByCategoryForNonmember($category, $region)
{
    $where_clause="";
    switch($category){
        case "전체보기":
            $where_clause='';
            break;
        case "1인분주문":
            $where_clause= " and is_portion = 'Y'";
            break;
        case "야식":
            $where_clause= " and is_night = 'Y'";
            break;
        case "프랜차이즈":
            $where_clause= " and is_franchise = 'Y'";
            break;
        case "요기요플러스":
            $where_clause= " and is_yogiyo_plus = 'Y'";
            break;
        case "치킨":
            $where_clause= " and restaurant.type = '치킨'";
            break;
        case "중국집":
            $where_clause= " and restaurant.type = '중국집'";
            break;
        case "피자/양식":
            $where_clause= " and restaurant.type = '피자/양식'";
            break;
        case "한식":
            $where_clause= " and restaurant.type = '한식'";
            break;
        case "분식":
            $where_clause= " and restaurant.type = '분식'";
            break;
        case "카페/디저트":
            $where_clause= " and restaurant.type = '카페/디저트'";
            break;
        case "족발/보쌈":
            $where_clause= " and restaurant.type = '족발/보쌈'";
            break;
        case "편의점/마트":
            $where_clause= " and restaurant.type = '편의점/마트'";
            break;
    }
    $pdo = pdoSqlConnect();
    $query = "select restaurant_name,
       restaurant_id,
       restaurant.region,
       ifnull(round(avg((taste_score + quantity_score + delivery_score) / 3), 1),0) as score,
       count(review_id)                                                   as review_num,
       count(review_owner_comment.contents)                               as owner_comment_num,
       concat(restaurant.delivery_discount, '원')                          as delivery_discount,
       best_menu,
       discount_rate,
       is_best_restaurant,
       is_cesco,
       datediff(now(), restaurant.created_at) as sales_days,
       estimated_delivery_time,
       image_url,
       is_deliver
from
    (restaurant left outer join (orders left outer join (review left outer join review_owner_comment using (review_id)) using (order_id)) using (restaurant_id))
         left outer join (select restaurant_id, group_concat(temp.menu_name order by temp.sales desc) as best_menu
                          from (select orders.restaurant_id, menu_name, sum(quantity) as sales
                                from orders
                                         left outer join (ordered_menu left outer join menu using (menu_id)) using (order_id)
                                group by restaurant_id, menu_name) as temp
                          group by restaurant_id) as t using (restaurant_id)
where restaurant.super_red_week = 'Y'
  and restaurant.region = ?
".$where_clause."group by restaurant.restaurant_id;";

    $st = $pdo->prepare($query);
    $st->execute([$region]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
function getNormalRestaurantByCategoryForNonmember($category, $region)
{
    $where_clause="";
    switch($category){
        case "전체보기":
            $where_clause='';
            break;
        case "1인분주문":
            $where_clause= " and is_portion = 'Y'";
            break;
        case "야식":
            $where_clause= " and is_night = 'Y'";
            break;
        case "프랜차이즈":
            $where_clause= " and is_franchise = 'Y'";
            break;
        case "요기요플러스":
            $where_clause= " and is_yogiyo_plus = 'Y'";
            break;
        case "치킨":
            $where_clause= " and restaurant.type = '치킨'";
            break;
        case "중국집":
            $where_clause= " and restaurant.type = '중국집'";
            break;
        case "피자/양식":
            $where_clause= " and restaurant.type = '피자/양식'";
            break;
        case "한식":
            $where_clause= " and restaurant.type = '한식'";
            break;
        case "분식":
            $where_clause= " and restaurant.type = '분식'";
            break;
        case "카페/디저트":
            $where_clause= " and restaurant.type = '카페/디저트'";
            break;
        case "족발/보쌈":
            $where_clause= " and restaurant.type = '족발/보쌈'";
            break;
        case "편의점/마트":
            $where_clause= " and restaurant.type = '편의점/마트'";
            break;
    }
    $pdo = pdoSqlConnect();
    $query = "select restaurant_name,
       restaurant_id,
       restaurant.region,
       ifnull(round(avg((taste_score + quantity_score + delivery_score) / 3), 1),0) as score,
       count(review_id)                                                   as review_num,
       count(review_owner_comment.contents)                               as owner_comment_num,
       concat(restaurant.delivery_discount, '원')                          as delivery_discount,
       best_menu,
       discount_rate,
       is_best_restaurant,
       is_cesco,
       datediff(now(), restaurant.created_at) as sales_days,
       estimated_delivery_time,
       image_url,
       is_deliver
from
    (restaurant left outer join (orders left outer join (review left outer join review_owner_comment using (review_id)) using (order_id)) using (restaurant_id))
         left outer join (select restaurant_id, group_concat(temp.menu_name order by temp.sales desc) as best_menu
                          from (select orders.restaurant_id, menu_name, sum(quantity) as sales
                                from orders
                                         left outer join (ordered_menu left outer join menu using (menu_id)) using (order_id)
                                group by restaurant_id, menu_name) as temp
                          group by restaurant_id) as t using (restaurant_id)
where restaurant.our_village_plus = 'N'
  and restaurant.super_red_week = 'N'
  and restaurant.region = ?
".$where_clause."group by restaurant.restaurant_id;";

    $st = $pdo->prepare($query);
    $st->execute([$region]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

// 4. 메뉴 검색
function findMenu($keyword, $user_id)
{
    $pdo = pdoSqlConnect();
    $query = "select restaurant_id,
       restaurant_name,
       restaurant.image_url,
       datediff(now(), restaurant.created_at)                                        as sales_days,
       restaurant.is_best_restaurant,
       restaurant.is_cesco,
       estimated_delivery_time,
       ifnull(round(avg((taste_score + quantity_score + delivery_score) / 3), 1), 0) as score,
       count(distinct review_id)                                                     as review_num,
       count(distinct review_owner_comment.contents)                                 as owner_comment_num,
       group_concat(menu_name separator ',')                                         as searched_menu
from (((restaurant join menu using (restaurant_id)) left outer join orders using (restaurant_id)) left outer join review using (order_id))
         left outer join review_owner_comment using (review_id)
where menu_name like concat('%', ?, '%') and restaurant.region = (select region from users where user_id = ?)
group by restaurant_id;";

    $st = $pdo->prepare($query);
    $st->execute([$keyword, $user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
function findMenuForNonmember($keyword, $region)
{
    $pdo = pdoSqlConnect();
    $query = "select restaurant_id,
       restaurant_name,
       restaurant.image_url,
       datediff(now(), restaurant.created_at)                                        as sales_days,
       restaurant.is_best_restaurant,
       restaurant.is_cesco,
       estimated_delivery_time,
       ifnull(round(avg((taste_score + quantity_score + delivery_score) / 3), 1), 0) as score,
       count(distinct review_id)                                                     as review_num,
       count(distinct review_owner_comment.contents)                                 as owner_comment_num,
       group_concat(menu_name separator ',')                                         as searched_menu
from (((restaurant join menu using (restaurant_id)) left outer join orders using (restaurant_id)) left outer join review using (order_id))
         left outer join review_owner_comment using (review_id)
where menu_name like concat('%', ?, '%') and restaurant.region = ?
group by restaurant_id;";

    $st = $pdo->prepare($query);
    $st->execute([$keyword, $region]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

// 5~7 최근 검색어 조회, 삭제, 전체삭제
function getRecentSearchKeyword($user_id){
    $pdo = pdoSqlConnect();
    $query = "select idx, keyword
    from recent_search_keyword
    where user_id=?
    order by updated_at desc;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;
    return $res;
}
function addRecentSearchKeyword($user_id,$keyword){
    $pdo = pdoSqlConnect();
    $query = "insert into recent_search_keyword (user_id, keyword) values (?,?);";

    $st = $pdo->prepare($query);
    $st->execute([$user_id,$keyword]);
    $st = null;
    $pdo = null;
}
function deleteRecentSearchKeyword($idx){
    $pdo = pdoSqlConnect();
    $query = "delete from recent_search_keyword where idx = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$idx]);

    $st = null;
    $pdo = null;
}
function deleteAllRecentSearchKeyword($user_id){
    $pdo = pdoSqlConnect();
    $query = "delete from recent_search_keyword where user_id = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id]);

    $st = null;
    $pdo = null;
}
function isKeywordExist($user_id,$keyword){
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS (select * FROM recent_search_keyword WHERE user_id = ? and keyword=?) as exist;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id,$keyword]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}
function isKeywordIdxExist($idx){
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS (select * FROM recent_search_keyword WHERE idx = ?) as exist;";

    $st = $pdo->prepare($query);
    $st->execute([$idx]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}
function updateKeywordSearchTime($user_id, $keyword){
    $pdo = pdoSqlConnect();
    $query = "update recent_search_keyword
set updated_at = now()
where user_id = ?
  and keyword = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id, $keyword]);

    $st = null;
    $pdo = null;

}

// 8. 식당 메인, 메뉴, 리뷰, 정보
function getRestaurantMain($rest_id)
{
    $pdo = pdoSqlConnect();
    $query = "select restaurant_name,
       ifnull(score,0) as score,
       ifnull(avg_taste,0) as avg_taste,
       ifnull(avg_quantity,0) as avg_quantity,
       ifnull(avg_delivery,0) as avg_delivery,
       concat(delivery_discount, '원')         as delivery_discount,
       estimated_delivery_time,
       concat(minimum_deliverable_price, '원') as minimum_deliverable_price,
       payment_type,
       concat(delivery_price, '원')            as delivery_price,
       owner_notice,
       ifnull(favorite_num,0) as favorite_num,
       ifnull(menu_num,0) as menu_num,
       ifnull(review_num,0) as review_num,
       ifnull(owner_comment_num,0) as owner_comment_num,
       background_url,
       share_url
from restaurant
         left outer join (
    select *
    from (select restaurant_id, count(*) as menu_num, favorite_num
          from menu
                   left outer join (
              select restaurant_id, count(*) as favorite_num
              from favorite_restaurant
              group by restaurant_id
          ) as t using (restaurant_id)
          group by restaurant_id) as t1
             left outer join (select restaurant_id,
                                     round(avg((taste_score + quantity_score + delivery_score) / 3), 1) as score,
                                     round(avg(taste_score), 1)                                         as avg_taste,
                                     round(avg(quantity_score), 1)                                      as avg_quantity,
                                     round(avg(delivery_score), 1)                                      as avg_delivery,
                                     count(*)                                                           as review_num,
                                     count(review_owner_comment.contents)                               as owner_comment_num
                              from (review
                                  left outer join orders using (order_id))
                                       left outer join review_owner_comment using (review_id)
                              group by restaurant_id) as t2 using (restaurant_id)
) as temp using (restaurant_id)
where restaurant_id = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$rest_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}
function getRestaurantBestMenu($rest_id)
{
    $pdo = pdoSqlConnect();
    $query="select  menu_name, menu_id, concat(price,'원') as price, image_url
from menu
         natural join (
    select menu_id, sum(quantity) as sales
    from ordered_menu
    group by menu_id
) as t
where restaurant_id=?
order by restaurant_id, sales desc limit 2;";
    $st = $pdo->prepare($query);
    $st->execute([$rest_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
function getRestaurantMenu($rest_id)
{
    $pdo = pdoSqlConnect();
    $query="select category, menu_name, menu_id, concat(price,'원') as price, image_url from menu
where restaurant_id=?;";
    $st = $pdo->prepare($query);
    $st->execute([$rest_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
function getRestaurantReview($user_id, $rest_id)
{
    $pdo = pdoSqlConnect();
    $query="select restaurant_id,
       review_id,
       users.nickname,
       profile_image_url.image_url                                   as profile_image,
       group_concat(distinct concat(menu_name, '/', quantity) separator ',') as order_info,
       group_concat(distinct review_image.image_url separator '; ')           as review_image,
       round((taste_score + quantity_score + delivery_score) / 3, 1) as score,
       taste_score,
       quantity_score,
       delivery_score,
       ifnull(status,0) as review_like_status,
        case
           when timestampdiff(hour, review.created_at, now()) < 1
               then concat(timestampdiff(minute, review.created_at, now()), '분전')
           when timestampdiff(day, review.created_at, now()) < 1
               then concat(timestampdiff(hour, review.created_at, now()), '시간전')
           when timestampdiff(day, review.created_at, now()) < 2 then '어제'
           when timestampdiff(day, review.created_at, now()) < 7
               then concat(timestampdiff(day, review.created_at, now()), '일전')
           else date_format(review.created_at, '%Y.%m.%d %H:%i')
           end                                                               as review_submit_time,
        review.contents                                               as review_contents,
        case
           when timestampdiff(hour, review_owner_comment.created_at, now()) < 1 then concat(
                   timestampdiff(minute, review_owner_comment.created_at, now()), '분전')
           when timestampdiff(day, review_owner_comment.created_at, now()) < 1 then concat(
                   timestampdiff(hour, review_owner_comment.created_at, now()), '시간전')
           when timestampdiff(day, review_owner_comment.created_at, now()) < 2 then '어제'
           when timestampdiff(day, review_owner_comment.created_at, now()) < 7 then concat(
                   timestampdiff(day, review_owner_comment.created_at, now()), '일전')
           else date_format(review_owner_comment.created_at, '%Y.%m.%d %H:%i')
           end                                                               as reply_submit_time,
        review_owner_comment.contents                                 as reply_contents
from ((((((review left outer join review_owner_comment using (review_id))
    left outer join review_image using (review_id))
    left outer join (orders left outer join (ordered_menu left outer join (select menu_id, menu_name from menu) as t using (menu_id)) using (order_id)) using (order_id))
    left outer join restaurant using (restaurant_id))
         left outer join (users left outer join profile_image_url using (type)) using (user_id))) left outer join (select review_id, status
from review_like
where user_id= ? ) as s using (review_id)
where restaurant_id = ?
group by review_id;";
    $st = $pdo->prepare($query);
    $st->execute([$user_id, $rest_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
function getRestaurantInfo($rest_id)
{
    $pdo = pdoSqlConnect();
    $query="select group_concat(owner_notice_image.image_url separator ';') as owner_notice_image,
       owner_notice,
       business_hours,
       concat(phone, '(요기요 제공 번호)')                             as phone,
       concat(region, ' ', address)                             as location,
       is_cesco,
       concat(minimum_deliverable_price, '원')                   as min_deliver_price,
       payment_type,
       restaurant_name,
       business_registration_number,
       origin_info
from restaurant
         join owner_notice_image using (restaurant_id)
where restaurant_id = ?;";
    $st = $pdo->prepare($query);
    $st->execute([$rest_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}

// 9. 메뉴 추가 옵션 선택
function getMenuOption($menu_id)
{
    $pdo = pdoSqlConnect();
    $query = "select menu_name, option_type, option_id, option_name, concat(extra_charge,'원') as extra_charge, share_url
from additional_option join menu using (menu_id)
where menu_id=?
order by option_type;";

    $st = $pdo->prepare($query);
    $st->execute([$menu_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

// 10. 터치 / 전화주문 조회
function getTouchOrderCount($user_id)
{
    $pdo = pdoSqlConnect();
    $query = "select count(*) as touch_num
from orders
where user_id= ? and order_type='터치';";
    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['touch_num']);
}
function getCallOrderCount($user_id)
{
    $pdo = pdoSqlConnect();
    $query = "select count(*) as call_num
from orders
where user_id=? and order_type='전화';";
    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['call_num']);
}
function getTouchOrderList($user_id)
{
    $pdo = pdoSqlConnect();
    $query = "select order_id, order_type, date_format(orders.created_at, '%Y.%m.%d %H:%i') as order_date, delivery_status, restaurant_name, restaurant.image_url,delivery_status, group_concat(distinct concat(menu_name, '/', quantity) separator ',') as order_info
       from ((orders left outer join ordered_menu using (order_id) ) left outer join restaurant using (restaurant_id)) left outer join menu using (menu_id)
where user_id= ? and order_type='터치'
group by order_id;";
    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
function getCallOrderList($user_id)
{
    $pdo = pdoSqlConnect();
    $query = "select order_id, order_type,date_format(orders.created_at, '%Y.%m.%d %H:%i') as order_date, delivery_status, restaurant_name, restaurant.image_url,delivery_status, group_concat(distinct concat(menu_name, '/', quantity) separator ',') as order_info
       from ((orders left outer join ordered_menu using (order_id) ) left outer join restaurant using (restaurant_id)) left outer join menu using (menu_id)
where user_id= ? and order_type='전화'
group by order_id;";
    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

// 11. 주문 상세 보기
function getOrderInfo($order_id)
{
    $pdo = pdoSqlConnect();
    // get order information
    $query = "select restaurant_name,
       delivery_status,
       order_id,
       date_format(orders.created_at, '%Y.%m.%d %H:%i')              as order_date,
       concat((select sum(price + IFNULL(extra_charge, 0))
               from (select ordered_menu.price,
                            ordered_menu.extra_charge
                     from (ordered_menu left outer join menu using (menu_id))
                              left outer join additional_option using (option_id)
                     where ordered_menu.order_id = ?) as temp), '원') as sum_menu_price,
       concat(orders.delivery_price, '원')                            as delivery_price,
       concat(orders.delivery_discount, '원')                         as delivery_discount,
       concat(((select sum(price + IFNULL(extra_charge, 0))
                from (select ordered_menu.price,
                             ordered_menu.extra_charge
                      from (ordered_menu left outer join menu using (menu_id))
                               left outer join additional_option using (option_id)
                      where ordered_menu.order_id = ?) as temp) + orders.delivery_price - orders.delivery_discount),
              '원')                                                   as actual_price,
       orders.payment_type,
        users.phone,
       concat(users.region, ' ', users.address)                      as user_location,
       request
from (orders join restaurant using (restaurant_id))
         join users using (user_id)
where orders.order_id = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$order_id, $order_id, $order_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}
function getOrderedMenu($order_id)
{
    $pdo = pdoSqlConnect();
    $query="select concat(menu_name, ' x ' ,quantity) as menu_list, ordered_menu.price, ifnull(concat(' - ',additional_option.option_type, ': ',option_name), '없음') as option_list, ordered_menu.extra_charge
from (ordered_menu left outer join menu using (menu_id))  left outer join additional_option using (option_id)
where ordered_menu.order_id=?;";
    $st = $pdo->prepare($query);
    $st->execute([$order_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

// 12~14. 주문표
function getOrderPad($user_id)
{
    $pdo = pdoSqlConnect();
    $query = "select * from order_pad where user_id = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
function isOrderPadEmpty($user_id){
    $pdo = pdoSqlConnect();
    $query = "SELECT NOT EXISTS (select * FROM order_pad where user_id = ?) as exist;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}
function getCurrentRestaurantID($user_id)
{
    $pdo = pdoSqlConnect();
    $query = "select restaurant_id
from order_pad 
where user_id = ?
limit 1;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['restaurant_id']);
}
function isItemExistInTheOrderPad($user_id, $menu_id, $option_id){
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS (select * FROM order_pad WHERE user_id = ? and menu_id = ? and option_id=?) as exist;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id, $menu_id, $option_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}
function addItemIntoOrderPad($rest_id, $user_id, $menu_id, $option_id, $quantity)
{
    $pdo = pdoSqlConnect();
    $query="insert into order_pad (restaurant_id, user_id, menu_id, option_id, quantity) values (?,?, ?,?,?);";
    $st = $pdo->prepare($query);
    $st->execute([$rest_id, $user_id, $menu_id, $option_id, $quantity]);

    $st = null;
    $pdo = null;
}
function deleteItemAtOrderPad($order_pad_id, $user_id)
{
    $pdo = pdoSqlConnect();
    $query="delete from order_pad
            where order_pad_id= ? and user_id = ?;";
    $st = $pdo->prepare($query);
    $st->execute([$order_pad_id,$user_id]);

    $st = null;
    $pdo = null;
}
function deleteAllItems($user_id)
{
    $pdo = pdoSqlConnect();
    $query="delete from order_pad where user_id = ?;";
    $st = $pdo->prepare($query);
    $st->execute([$user_id]);

    $st = null;
    $pdo = null;
}


// 15. 주문하기
function addOrders($order_id, $rest_id, $user_id, $payment_type, $request, $order_type, $user_location)
{
    $pdo = pdoSqlConnect();
    $delivery_price =getDeliveryPrice($rest_id);
    $delivery_discount=getDeliveryDiscount($rest_id);
    $query="insert into orders (order_id, restaurant_id, user_id, payment_type, request, order_type, delivery_price, delivery_discount, user_location)
values (?,?,?,?,?,?,?,?,?);";
    $st = $pdo->prepare($query);
    $st->execute([$order_id, $rest_id, $user_id, $payment_type, $request, $order_type, $delivery_price, $delivery_discount, $user_location]);
    $st = null;
    $pdo = null;
}
function addOrderedMenu($order_id, $menu_id, $quantity, $option_id)
{
    $pdo = pdoSqlConnect();
    $price = getMenuPrice($menu_id);
    $extra_charge = getOptionPrice($option_id);

    $query="insert into ordered_menu values (?,?,?,?,?,?);";
    $st = $pdo->prepare($query);
    $st->execute([$order_id, $menu_id, $quantity, $price, $option_id, $extra_charge]);

    $st = null;
    $pdo = null;
}
function getDeliveryPrice($rest_id)
{
    $pdo = pdoSqlConnect();
    $query = "select delivery_price from restaurant where restaurant_id=?;";

    $st = $pdo->prepare($query);
    $st->execute([$rest_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['delivery_price']);
}
function getDeliveryDiscount($rest_id)
{
    $pdo = pdoSqlConnect();
    $query = "select delivery_discount from restaurant where restaurant_id=?;";

    $st = $pdo->prepare($query);
    $st->execute([$rest_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['delivery_discount']);
}
function getMenuPrice($menu_id)
{
    $pdo = pdoSqlConnect();
    $query = "select price from menu where menu_id=?;";

    $st = $pdo->prepare($query);
    $st->execute([$menu_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['price']);
}
function getOptionPrice($option_id)
{
    if($option_id == 0)
        return 0;
    $pdo = pdoSqlConnect();
    $query = "select extra_charge from additional_option where option_id=?;";

    $st = $pdo->prepare($query);
    $st->execute([$option_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['extra_charge']);
}
function getUserLocation($user_id){
    $pdo = pdoSqlConnect();
    $query = "select region, address
    from users
    where user_id=?;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}
// 16. 재주문

function getOrderedMenuForReorder($order_id){
    $pdo = pdoSqlConnect();
    $query = "select restaurant_id, menu_id, option_id, quantity
from ordered_menu join orders using (order_id)
where order_id = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$order_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

// 17. 마이요기요
function getMyYogiyo($user_id)
{
    $pdo = pdoSqlConnect();
    $query = "select nickname, type, image_url, review_num
from (users join profile_image_url using (type)) join (select user_id, count(*) as review_num
from review left outer join orders using (order_id)
group by user_id) as t using (user_id)
where user_id=?;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}

// 18. 사용자 정보
function getUserInfo($user_id)
{
    $pdo = pdoSqlConnect();
    $query = "select `email`, concat(users.phone".",' ') as phone, nickname
from users
where user_id=?;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}

// 19. 등록한 카드
function getCardInfo($user_id)
{
    $pdo = pdoSqlConnect();
    $query = "select card_type, card_number
from registered_card
where user_id=? and is_deleted = 0;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

// 20~21. 카드 추가 / 삭제
function isCardExist($user_id, $card_number)
{
    $pdo = pdoSqlConnect();
    // user_id, rest_id 존재하는지 확인
    $query = "SELECT EXISTS(select * FROM registered_card WHERE user_id = ? and card_number = ?) as exist;";
    $st = $pdo->prepare($query);
    $st->execute([$user_id, $card_number]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}
function isCardDeleted($user_id, $card_number)
{
    $pdo = pdoSqlConnect();
    $query="select is_deleted
from registered_card
where user_id=? and card_number=?;";
    $st = $pdo->prepare($query);
    $st->execute([$user_id, $card_number]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['is_deleted']);
}
function addCard($user_id, $card_type, $card_number, $expiration_date, $cvc, $password, $resident_registration_number)
{
    $pdo = pdoSqlConnect();
    $query="insert into registered_card values (?,?,?,?,?,?,?,'0');";
    $st = $pdo->prepare($query);
    $st->execute([$user_id, $card_type, $card_number, $expiration_date, $cvc, $password, $resident_registration_number]);

    $st = null;
    $pdo = null;
}
function updateCardToActive($user_id, $card_number)
{
    $pdo = pdoSqlConnect();
    $query="update registered_card
            set is_deleted=0
            where user_id = ? and card_number=?";

    $st = $pdo->prepare($query);
    $st->execute([$user_id, $card_number]);

    $st = null;
    $pdo = null;
}
function updateCardToUnactive($user_id, $card_number)
{
    $pdo = pdoSqlConnect();
    $query="update registered_card
            set is_deleted=1
            where user_id = ? and card_number=?";

    $st = $pdo->prepare($query);
    $st->execute([$user_id, $card_number]);

    $st = null;
    $pdo = null;
}

// 22. 결제 비밀번호 변경
function getPaymentPassword($user_id)
{
    $pdo = pdoSqlConnect();
    $query = "select payment_password
    from users
    where user_id=?;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['payment_password']);
}
function updatePaymentPassword($payment_password, $user_id)
{
    $pdo = pdoSqlConnect();
    $query = "update users set payment_password = ? where user_id=?;";

    $st = $pdo->prepare($query);
    $st->execute([$payment_password, $user_id]);

    $st = null;
    $pdo = null;

}

// 23. 휴대전화번호 변경
function updatePhone($phone,$user_id)
{
    $pdo = pdoSqlConnect();
    $query = "update users set phone = ? where user_id=?;";

    $st = $pdo->prepare($query);
    $st->execute([$phone, $user_id]);

    $st = null;
    $pdo = null;

}

// 24. 닉네임 변경
function updateNickname($nickname,$user_id)
{
    $pdo = pdoSqlConnect();
    $query = "update users set nickname = ? where user_id=?;";

    $st = $pdo->prepare($query);
    $st->execute([$nickname,$user_id]);

    $st = null;
    $pdo = null;

}

// 25. 배달 주소 변경
function updateLocation($region, $address, $user_id)
{
    $pdo = pdoSqlConnect();
    $query = "update users set region = ? where user_id=?;";
    $st = $pdo->prepare($query);
    $st->execute([$region, $user_id]);

    $query = "update users set address = ? where user_id=?;";
    $st = $pdo->prepare($query);
    $st->execute([$address, $user_id]);

    if(!isRecentLocationExist($user_id, $region, $address)){
        $query = "insert into recent_user_location (user_id, region, address) values (?, ?, ?);";
        $st = $pdo->prepare($query);
        $st->execute([$user_id, $region, $address]);
    }
    else{
        updateUserLocationTime($user_id, $region, $address);
    }

    $st = null;
    $pdo = null;
}

// 26~27. 최근 배달위치 조회 / 삭제
function isRecentLocationExist($user_id, $region, $address){
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS (select * FROM recent_user_location WHERE user_id = ? and region = ? and address=? ) as exist;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id, $region, $address]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}
function getRecentLocation($user_id){
    $pdo = pdoSqlConnect();
    $query = "select idx, concat(region, ' ', address) as location
from recent_user_location
where user_id = ?
order by updated_at desc;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;
    return $res;
}
function deleteRecentLocation($idx){
    $pdo = pdoSqlConnect();
    $query = "delete from recent_user_location where idx = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$idx]);

    $st = null;
    $pdo = null;
}
function isLocationIdxExist($idx){
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS (select * FROM recent_user_location WHERE idx = ?) as exist;";

    $st = $pdo->prepare($query);
    $st->execute([$idx]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}
function updateUserLocationTime($user_id, $region, $address){
    $pdo = pdoSqlConnect();
    $query = "update recent_user_location
set updated_at = now()
where user_id = ?
  and region = ?
  and address = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id, $region, $address]);

    $st = null;
    $pdo = null;

}

// 28~29. 회원가입 / 탈퇴
function addUser($nickname, $email, $password, $phone, $region, $address, $payment_password)
{
    $pdo = pdoSqlConnect();
    $query = "insert into users (nickname, email, password, phone, region, address, payment_password) values (?,?,?,?,?,?,?);";

    $st = $pdo->prepare($query);
    $st->execute([$nickname, $email, $password, $phone, $region, $address,$payment_password]);

    $st = null;
    $pdo = null;

}
function deleteUser($user_id)
{
    $pdo = pdoSqlConnect();
    $query = "update users set is_deleted = 1 where user_id=?;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id]);

    $st = null;
    $pdo = null;

}

// 30. 리뷰 작성
function isReviewExist($order_id)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS (select * FROM review WHERE order_id = ?) as exist;";

    $st = $pdo->prepare($query);
    $st->execute([$order_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);

}
function addReview($order_id, $contents, $taste_score, $quantity_score, $delivery_score)
{
    $pdo = pdoSqlConnect();
    $query = "insert into review (order_id, contents, taste_score, quantity_score, delivery_score) values (?,?,?,?,?);";
    $st = $pdo->prepare($query);
    $st->execute([$order_id, $contents, $taste_score, $quantity_score, $delivery_score]);

    $st = null;
    $pdo = null;
}
function getReviewId($order_id)
{
    $pdo = pdoSqlConnect();
    $query = "select review_id from review where order_id = ?";
    $st = $pdo->prepare($query);
    $st->execute([$order_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;
    return intval($res[0]['review_id']);
}
function addReviewImage($review_id, $image_url)
{
    $pdo = pdoSqlConnect();
    $query = "insert into review_image values (?,?);";
    $st = $pdo->prepare($query);
    $st->execute([$review_id, $image_url]);

    $st = null;
    $pdo = null;
}

// 31. 리뷰 삭제
function deleteReview($review_id){
    $pdo = pdoSqlConnect();
    $query = "delete from review where review_id = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$review_id]);

    $st = null;
    $pdo = null;
}

// 32. 리뷰 추천 / 취소
function isReviewLikeExist($user_id, $review_id)
{
    $pdo = pdoSqlConnect();
    // user_id, rest_id 존재하는지 확인
    $query = "SELECT EXISTS(select * FROM review_like WHERE user_id = ? and review_id = ?) as exist;";
    $st = $pdo->prepare($query);
    $st->execute([$user_id, $review_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}
function addReviewLike($user_id, $review_id)
{
    $pdo = pdoSqlConnect();
    $query="insert into review_like (user_id, review_id) values (?, ?);";
    $st = $pdo->prepare($query);
    $st->execute([$user_id, $review_id]);

    $st = null;
    $pdo = null;
}
function getReviewLikeStatus($user_id, $review_id)
{
    $pdo = pdoSqlConnect();
    $query="select status
from review_like
where user_id= ? and review_id=?;";
    $st = $pdo->prepare($query);
    $st->execute([$user_id, $review_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['status']);
}
function updateReviewLikeToTrue($user_id, $review_id)
{
    $pdo = pdoSqlConnect();
    $query="update review_like
            set status=1
            where user_id = ?
              and review_id = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id, $review_id]);

    $st = null;
    $pdo = null;
}
function updateReviewLikeToFalse($user_id, $review_id)
{
    $pdo = pdoSqlConnect();
    $query="update review_like
            set status=0
            where user_id = ?
              and review_id = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id, $review_id]);

    $st = null;
    $pdo = null;
}

// 33. 리뷰 신고
function reportReview($user_id, $review_id){
    $pdo = pdoSqlConnect();
    $query = "insert into review_report (user_id, review_id) values (?, ?);";

    $st = $pdo->prepare($query);
    $st->execute([$user_id, $review_id]);

    $st = null;
    $pdo = null;
}
function isReviewAlreadyReport($user_id, $review_id){
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS (select * FROM review_report WHERE user_id = ? and review_id=?) as exist;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id, $review_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}

// 34.1. 우리동네 찜 많은 음식점 (찜한 수 기준 정렬)
function getFavoriteMostRestaurant($user_id)
{
    $pdo = pdoSqlConnect();
    // 우리 동네 플러스
    $query = "select restaurant.restaurant_id,
       restaurant_name,
       image_url,
       best_menu,
       ifnull(fav_cnt, 0) as fav_cnt,
       ifnull(score, 0) as score,
       ifnull(review_num, 0) as review_num
from ((restaurant
    left outer join (select restaurant_id, count(*) as fav_cnt
                     from favorite_restaurant
                     group by restaurant_id
    ) as temp using (restaurant_id))
         left outer join (select restaurant_id,
                                 round(avg((taste_score + quantity_score + delivery_score) / 3), 1) as score,
                                 count(*)                                                           as review_num
                          from (review
                                   left outer join orders using (order_id))
                          group by restaurant_id) as temp2 using (restaurant_id))
         left outer join (select restaurant_id, group_concat(temp.menu_name order by temp.sales desc) as best_menu
                          from (select orders.restaurant_id, menu_name, sum(quantity) as sales
                                from orders
                                         left outer join (ordered_menu left outer join menu using (menu_id)) using (order_id)
                                group by restaurant_id, menu_name) as temp
                          group by restaurant_id) as grouped_menu using (restaurant_id)
where restaurant.region = (select region from users where user_id = ?)
  and is_deliver = 'Y'
order by fav_cnt desc
limit 20;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
// 35.1. 학익동 오늘만 할인 (배달할인 or 할인율 > 0)
function getTodayDeliveryDiscountRestaurant($user_id)
{
    $pdo = pdoSqlConnect();
    // 우리 동네 플러스
    $query = "select restaurant_id,
       restaurant_name,
       ifnull(round(avg((taste_score + quantity_score + delivery_score) / 3), 1), 0) as score,
       count(review_id)                                                              as review_num,
       concat(restaurant.delivery_discount,'원 할인') as delivery_discount,
       if(restaurant.delivery_price=0,'배달비 무료',' ') as is_delivery_free,
       image_url
from restaurant left outer join (orders left outer join review using (order_id)) using (restaurant_id)
where restaurant.delivery_discount >0 and restaurant.region = (select region from users where user_id = ?)
group by restaurant_id;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
function getTodayDiscountRateRestaurant($user_id)
{
    $pdo = pdoSqlConnect();
    // 우리 동네 플러스
    $query = "select restaurant_id,
       restaurant_name,
       ifnull(round(avg((taste_score + quantity_score + delivery_score) / 3), 1), 0) as score,
       count(review_id)                                                              as review_num,
       concat(round(discount_rate*100,0), '% 할인') as discount_rate,
       if(restaurant.delivery_price=0,'배달비 무료',' ') as is_delivery_free,
       image_url
from restaurant left outer join (orders left outer join review using (order_id)) using (restaurant_id)
where restaurant.discount_rate >0 and restaurant.region = (select region from users where user_id = ?)
group by restaurant_id;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
// 36.1. 요즘 뜨는 우리동네 음식점 (최근 30일 이내 식당 주문량 기준)
function getRecentBestRestaurant($user_id)
{
    $pdo = pdoSqlConnect();
    // 우리 동네 플러스
    $query = "select restaurant_id,
       restaurant_name,
       ifnull(round(avg((taste_score + quantity_score + delivery_score) / 3), 1), 0) as score,
       count(review_id)                                                              as review_num,
       best_menu,
       image_url
from (restaurant left outer join (orders left outer join review using (order_id)) using (restaurant_id))
         left outer join (select restaurant_id, group_concat(temp.menu_name order by temp.sales desc) as best_menu
                          from (select orders.restaurant_id, menu_name, sum(quantity) as sales
                                from orders
                                         left outer join (ordered_menu left outer join menu using (menu_id)) using (order_id)
                                group by restaurant_id, menu_name) as temp
                          group by restaurant_id) as grouped_menu using (restaurant_id)
where timestampdiff(day, restaurant.created_at, now()) <= 30 and restaurant.region = (select region from users where user_id = ?)
group by restaurant_id
order by count(order_id) desc;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
// 37.1. 학익동 배달비 무료
function getDeliverFreeRestaurant($user_id)
{
    $pdo = pdoSqlConnect();
    // 우리 동네 플러스
    $query = "select restaurant_id,
       restaurant_name,
       ifnull(round(avg((taste_score + quantity_score + delivery_score) / 3), 1), 0) as score,
       count(review_id)                                                              as review_num,
       if(restaurant.delivery_price=0,'배달비 무료',' ') as is_delivery_free,
       concat('최소주문 ',format(restaurant.minimum_deliverable_price,0),'원') as min_deliver_price,
       image_url
from restaurant left outer join (orders left outer join review using (order_id)) using (restaurant_id)
         where restaurant.delivery_price = 0 and restaurant.region = (select region from users where user_id = ?)
group by restaurant_id;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
// 38.1. 최근 7일 동안 리뷰가 많아요! (최근 7일 이내 작성된 리뷰 개수 기준 정렬)
function getMostReviewRestaurant($user_id)
{
    $pdo = pdoSqlConnect();
    // 우리 동네 플러스
    $query = "select restaurant_id,
       restaurant_name,
       ifnull(round(avg((taste_score + quantity_score + delivery_score) / 3), 1), 0) as score,
       count(review_id)                                                              as review_num,
       best_menu,
       image_url
from ((restaurant left outer join (orders left outer join review using (order_id)) using (restaurant_id))
         left outer join (select restaurant_id, count(*) as last_7days_review_cnt
                          from (orders left outer join review using (order_id))
                                   left outer join restaurant using (restaurant_id)
                          where timestampdiff(day, review.created_at, now()) <= 7
                          group by restaurant_id) as temp using (restaurant_id))
         left outer join (select restaurant_id, group_concat(temp.menu_name order by temp.sales desc) as best_menu
                          from (select orders.restaurant_id, menu_name, sum(quantity) as sales
                                from orders
                                         left outer join (ordered_menu left outer join menu using (menu_id)) using (order_id)
                                group by restaurant_id, menu_name) as temp
                          group by restaurant_id) as grouped_menu using (restaurant_id)
where restaurant.region = (select region from users where user_id = ?)
group by restaurant_id
order by last_7days_review_cnt desc;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
// 39.1. 요기요 플러스 맛집
function getYogiyoPlusRestaurant($user_id)
{
    $pdo = pdoSqlConnect();
    // 우리 동네 플러스
    $query = "select restaurant_id,
       restaurant_name,
       ifnull(round(avg((taste_score + quantity_score + delivery_score) / 3), 1), 0) as score,
       count(review_id)                                                              as review_num,
       best_menu,
       image_url
from (restaurant left outer join (orders left outer join review using (order_id)) using (restaurant_id))
         left outer join (select restaurant_id, group_concat(temp.menu_name order by temp.sales desc) as best_menu
                          from (select orders.restaurant_id, menu_name, sum(quantity) as sales
                                from orders
                                         left outer join (ordered_menu left outer join menu using (menu_id)) using (order_id)
                                group by restaurant_id, menu_name) as temp
                          group by restaurant_id) as grouped_menu using (restaurant_id)
where restaurant.is_yogiyo_plus = 'Y' and restaurant.region = (select region from users where user_id = ?)
group by restaurant_id;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
// 40.1. 가장 빨리 배달되요 (배달시간 기준 정렬)
function getFastDeliverRestaurant($user_id)
{
    $pdo = pdoSqlConnect();
    // 우리 동네 플러스
    $query = "select restaurant_id,
       restaurant_name,
       ifnull(round(avg((taste_score + quantity_score + delivery_score) / 3), 1), 0) as score,
       count(review_id)                                                              as review_num,
       estimated_delivery_time,
       best_menu,
       image_url
from (restaurant left outer join (orders left outer join review using (order_id)) using (restaurant_id))
         left outer join (select restaurant_id, group_concat(temp.menu_name order by temp.sales desc) as best_menu
                          from (select orders.restaurant_id, menu_name, sum(quantity) as sales
                                from orders
                                         left outer join (ordered_menu left outer join menu using (menu_id)) using (order_id)
                                group by restaurant_id, menu_name) as temp
                          group by restaurant_id) as grouped_menu using (restaurant_id)
where restaurant.region = (select region from users where user_id = ?)
group by restaurant_id
order by restaurant.estimated_delivery_time;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
// 41.1. 새로 오픈했어요 (개업한 지 7일 이하)
function getNewlyOpenedRestaurant($user_id)
{
    $pdo = pdoSqlConnect();
    // 우리 동네 플러스
    $query = "select restaurant_id, restaurant_name, image_url
from restaurant
where timestampdiff(day, restaurant.created_at, now()) <= 7
  and restaurant.region = (select region from users where user_id = ?);";

    $st = $pdo->prepare($query);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

// 34.2. (비회원)우리동네 찜 많은 음식점 (찜한 수 기준 정렬)
function getFavoriteMostRestaurantForNonmenber($region)
{
    $pdo = pdoSqlConnect();
    // 우리 동네 플러스
    $query = "select restaurant.restaurant_id,
       restaurant_name,
       image_url,
       best_menu,
       ifnull(fav_cnt, 0) as fav_cnt,
       ifnull(score, 0) as score,
       ifnull(review_num, 0) as review_num
from ((restaurant
    left outer join (select restaurant_id, count(*) as fav_cnt
                     from favorite_restaurant
                     group by restaurant_id
    ) as temp using (restaurant_id))
         left outer join (select restaurant_id,
                                 round(avg((taste_score + quantity_score + delivery_score) / 3), 1) as score,
                                 count(*)                                                           as review_num
                          from (review
                                   left outer join orders using (order_id))
                          group by restaurant_id) as temp2 using (restaurant_id))
         left outer join (select restaurant_id, group_concat(temp.menu_name order by temp.sales desc) as best_menu
                          from (select orders.restaurant_id, menu_name, sum(quantity) as sales
                                from orders
                                         left outer join (ordered_menu left outer join menu using (menu_id)) using (order_id)
                                group by restaurant_id, menu_name) as temp
                          group by restaurant_id) as grouped_menu using (restaurant_id)
where restaurant.region = ?
  and is_deliver = 'Y'
order by fav_cnt desc
limit 20;";

    $st = $pdo->prepare($query);
    $st->execute([$region]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
// 35.2. (비회원)학익동 오늘만 할인 (배달할인 or 할인율 > 0)
function getTodayDeliveryDiscountRestaurantForNonmenber($region)
{
    $pdo = pdoSqlConnect();
    // 우리 동네 플러스
    $query = "select restaurant_id,
       restaurant_name,
       ifnull(round(avg((taste_score + quantity_score + delivery_score) / 3), 1), 0) as score,
       count(review_id)                                                              as review_num,
       concat(restaurant.delivery_discount,'원 할인') as delivery_discount,
       if(restaurant.delivery_price=0,'배달비 무료',' ') as is_delivery_free,
       image_url
from restaurant left outer join (orders left outer join review using (order_id)) using (restaurant_id)
where restaurant.delivery_discount >0 and restaurant.region = ?
group by restaurant_id;";

    $st = $pdo->prepare($query);
    $st->execute([$region]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
function getTodayDiscountRateRestaurantForNonmenber($region)
{
    $pdo = pdoSqlConnect();
    // 우리 동네 플러스
    $query = "select restaurant_id,
       restaurant_name,
       ifnull(round(avg((taste_score + quantity_score + delivery_score) / 3), 1), 0) as score,
       count(review_id)                                                              as review_num,
       concat(round(discount_rate*100,0), '% 할인') as discount_rate,
       if(restaurant.delivery_price=0,'배달비 무료',' ') as is_delivery_free,
       image_url
from restaurant left outer join (orders left outer join review using (order_id)) using (restaurant_id)
where restaurant.discount_rate >0 and restaurant.region =?
group by restaurant_id;";

    $st = $pdo->prepare($query);
    $st->execute([$region]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
// 36.2. (비회원)요즘 뜨는 우리동네 음식점 (최근 30일 이내 식당 주문량 기준)
function getRecentBestRestaurantForNonmenber($region)
{
    $pdo = pdoSqlConnect();
    // 우리 동네 플러스
    $query = "select restaurant_id,
       restaurant_name,
       ifnull(round(avg((taste_score + quantity_score + delivery_score) / 3), 1), 0) as score,
       count(review_id)                                                              as review_num,
       best_menu,
       image_url
from (restaurant left outer join (orders left outer join review using (order_id)) using (restaurant_id))
         left outer join (select restaurant_id, group_concat(temp.menu_name order by temp.sales desc) as best_menu
                          from (select orders.restaurant_id, menu_name, sum(quantity) as sales
                                from orders
                                         left outer join (ordered_menu left outer join menu using (menu_id)) using (order_id)
                                group by restaurant_id, menu_name) as temp
                          group by restaurant_id) as grouped_menu using (restaurant_id)
where timestampdiff(day, restaurant.created_at, now()) <= 30 and restaurant.region = ?
group by restaurant_id
order by count(order_id) desc;";

    $st = $pdo->prepare($query);
    $st->execute([$region]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
// 37.2. (비회원)학익동 배달비 무료
function getDeliverFreeRestaurantForNonmenber($region)
{
    $pdo = pdoSqlConnect();
    // 우리 동네 플러스
    $query = "select restaurant_id,
       restaurant_name,
       ifnull(round(avg((taste_score + quantity_score + delivery_score) / 3), 1), 0) as score,
       count(review_id)                                                              as review_num,
       if(restaurant.delivery_price=0,'배달비 무료',' ') as is_delivery_free,
       concat('최소주문 ',format(restaurant.minimum_deliverable_price,0),'원') as min_deliver_price,
       image_url
from restaurant left outer join (orders left outer join review using (order_id)) using (restaurant_id)
         where restaurant.delivery_price = 0 and restaurant.region = ?
group by restaurant_id;";

    $st = $pdo->prepare($query);
    $st->execute([$region]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
// 38.2. (비회원)최근 7일 동안 리뷰가 많아요! (최근 7일 이내 작성된 리뷰 개수 기준 정렬)
function getMostReviewRestaurantForNonmenber($region)
{
    $pdo = pdoSqlConnect();
    // 우리 동네 플러스
    $query = "select restaurant_id,
       restaurant_name,
       ifnull(round(avg((taste_score + quantity_score + delivery_score) / 3), 1), 0) as score,
       count(review_id)                                                              as review_num,
       best_menu,
       image_url
from ((restaurant left outer join (orders left outer join review using (order_id)) using (restaurant_id))
         left outer join (select restaurant_id, count(*) as last_7days_review_cnt
                          from (orders left outer join review using (order_id))
                                   left outer join restaurant using (restaurant_id)
                          where timestampdiff(day, review.created_at, now()) <= 7
                          group by restaurant_id) as temp using (restaurant_id))
         left outer join (select restaurant_id, group_concat(temp.menu_name order by temp.sales desc) as best_menu
                          from (select orders.restaurant_id, menu_name, sum(quantity) as sales
                                from orders
                                         left outer join (ordered_menu left outer join menu using (menu_id)) using (order_id)
                                group by restaurant_id, menu_name) as temp
                          group by restaurant_id) as grouped_menu using (restaurant_id)
where restaurant.region =?
group by restaurant_id
order by last_7days_review_cnt desc;";

    $st = $pdo->prepare($query);
    $st->execute([$region]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
// 39.2. (비회원)요기요 플러스 맛집
function getYogiyoPlusRestaurantForNonmenber($region)
{
    $pdo = pdoSqlConnect();
    // 우리 동네 플러스
    $query = "select restaurant_id,
       restaurant_name,
       ifnull(round(avg((taste_score + quantity_score + delivery_score) / 3), 1), 0) as score,
       count(review_id)                                                              as review_num,
       best_menu,
       image_url
from (restaurant left outer join (orders left outer join review using (order_id)) using (restaurant_id))
         left outer join (select restaurant_id, group_concat(temp.menu_name order by temp.sales desc) as best_menu
                          from (select orders.restaurant_id, menu_name, sum(quantity) as sales
                                from orders
                                         left outer join (ordered_menu left outer join menu using (menu_id)) using (order_id)
                                group by restaurant_id, menu_name) as temp
                          group by restaurant_id) as grouped_menu using (restaurant_id)
where restaurant.is_yogiyo_plus = 'Y' and restaurant.region = ?
group by restaurant_id;";

    $st = $pdo->prepare($query);
    $st->execute([$region]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
// 40.2. (비회원)가장 빨리 배달되요 (배달시간 기준 정렬)
function getFastDeliverRestaurantForNonmenber($region)
{
    $pdo = pdoSqlConnect();
    // 우리 동네 플러스
    $query = "select restaurant_id,
       restaurant_name,
       ifnull(round(avg((taste_score + quantity_score + delivery_score) / 3), 1), 0) as score,
       count(review_id)                                                              as review_num,
       estimated_delivery_time,
       best_menu,
       image_url
from (restaurant left outer join (orders left outer join review using (order_id)) using (restaurant_id))
         left outer join (select restaurant_id, group_concat(temp.menu_name order by temp.sales desc) as best_menu
                          from (select orders.restaurant_id, menu_name, sum(quantity) as sales
                                from orders
                                         left outer join (ordered_menu left outer join menu using (menu_id)) using (order_id)
                                group by restaurant_id, menu_name) as temp
                          group by restaurant_id) as best_menu using (restaurant_id)
where restaurant.region = ?
group by restaurant_id
order by restaurant.estimated_delivery_time;";

    $st = $pdo->prepare($query);
    $st->execute([$region]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
// 41.2. (비회원)새로 오픈했어요 (개업한 지 7일 이하)
function getNewlyOpenedRestaurantForNonmenber($region)
{
    $pdo = pdoSqlConnect();
    // 우리 동네 플러스
    $query = "select restaurant_id, restaurant_name, image_url
from restaurant
where timestampdiff(day, restaurant.created_at, now()) <= 7
  and restaurant.region = ?";

    $st = $pdo->prepare($query);
    $st->execute([$region]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}


function getUserIdByEmail($email){
    $pdo = pdoSqlConnect();
    $query = "select user_id from users where email = ?";
    $st = $pdo->prepare($query);
    $st->execute([$email]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;
    return intval($res[0]['user_id']);
}
// ******** check validation ********
function isValidUser($email, $password){
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS (select * FROM users WHERE email = ? and password = ? and is_deleted = 0) as exist;";

    $st = $pdo->prepare($query);
    $st->execute([$email, $password]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}                     // 201
function isValidRestaurant($rest_id)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS (select * FROM restaurant WHERE restaurant_id = ?) as exist;";

    $st = $pdo->prepare($query);
    $st->execute([$rest_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}       // 202
function isValidMenu($menu_id, $rest_id)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS (select * FROM menu WHERE menu_id = ? and restaurant_id = ?) as exist;";

    $st = $pdo->prepare($query);
    $st->execute([$menu_id, $rest_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}             // 203
function isValidOption($option_id, $menu_id)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS (select * FROM additional_option WHERE option_id = ? and menu_id = ?) as exist;";

    $st = $pdo->prepare($query);
    $st->execute([$option_id, $menu_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}         // 204
function isValidReview($review_id)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS (select * FROM review WHERE review_id = ?) as exist;";

    $st = $pdo->prepare($query);
    $st->execute([$review_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}         // 205
function isValidOrder($order_id)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS (select * FROM orders WHERE order_id = ?) as exist;";

    $st = $pdo->prepare($query);
    $st->execute([$order_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}           // 206
function isValidCategory($category)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS (select * FROM restaurant_category WHERE category_name = ?) as exist;";

    $st = $pdo->prepare($query);
    $st->execute([$category]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}        // 207
function isValidOrderPadId($order_pad_id){
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS (select * FROM order_pad WHERE order_pad_id = ?) as exist;";

    $st = $pdo->prepare($query);
    $st->execute([$order_pad_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}                     // 208
function isPhoneExist($phone){
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS (select * FROM users WHERE phone = ?) as exist;";

    $st = $pdo->prepare($query);
    $st->execute([$phone]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}
function isEmailExist($email){
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS (select * FROM users WHERE email = ?) as exist;";

    $st = $pdo->prepare($query);
    $st->execute([$email]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}
function isNicknameExist($nickname){
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS (select * FROM users WHERE nickname = ?) as exist;";

    $st = $pdo->prepare($query);
    $st->execute([$nickname]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}
// CREATE
//    function addMaintenance($message){
//        $pdo = pdoSqlConnect();
//        $query = "INSERT INTO MAINTENANCE (MESSAGE) VALUES (?);";
//
//        $st = $pdo->prepare($query);
//        $st->execute([$message]);
//
//        $st = null;
//        $pdo = null;
//
//    }


// UPDATE
//    function updateMaintenanceStatus($message, $status, $no){
//        $pdo = pdoSqlConnect();
//        $query = "UPDATE MAINTENANCE
//                        SET MESSAGE = ?,
//                            STATUS  = ?
//                        WHERE NO = ?";
//
//        $st = $pdo->prepare($query);
//        $st->execute([$message, $status, $no]);
//        $st = null;
//        $pdo = null;
//    }

// RETURN BOOLEAN
//    function isRedundantEmail($email){
//        $pdo = pdoSqlConnect();
//        $query = "SELECT EXISTS(SELECT * FROM USER_TB WHERE EMAIL= ?) AS exist;";
//
//
//        $st = $pdo->prepare($query);
//        //    $st->execute([$param,$param]);
//        $st->execute([$email]);
//        $st->setFetchMode(PDO::FETCH_ASSOC);
//        $res = $st->fetchAll();
//
//        $st=null;$pdo = null;
//
//        return intval($res[0]["exist"]);
//
//    }
