// https://www.avito.ru/izhevsk?q=%D0%B1%D0%BE%D0%BB%D0%B2%D0%B0%D0%BD%D0%BA%D0%B8+cd-r
// https://www.avito.ru/izhevsk/tovary_dlya_kompyutera/setevoe_oborudovanie-ASgBAgICAUTGB75O?geoCoords=56.852593%2C53.204843&radius=1&q=%D0%BC%D0%BE%D0%B4%D0%B5%D0%BC
// https://www.avito.ru/izhevsk/nastolnye_kompyutery?pmax=7000
// https://www.avito.ru/izhevsk/avtomobili/bmw-ASgBAgICAUTgtg3klyg?radius=0
// для графика https://www.avito.ru/izhevsk/telefony/aksessuary-ASgBAgICAUSeAvZN?q=%D1%87%D0%B5%D1%85%D0%BE%D0%BB+%D0%BD%D0%B0+iphone+7 , https://www.avito.ru/izhevsk/krasota_i_zdorove?geoCoords=56.85230160470812%2C53.205877562122744&radius=5&q=%D0%BC%D0%B0%D1%81%D0%BA%D0%B0+%D0%BC%D0%B5%D0%B4%D0%B8%D1%86%D0%B8%D0%BD%D1%81%D0%BA%D0%B0%D1%8F


/* таблицы для бд

CREATE TABLE vk_users (
      user_id INT AUTO_INCREMENT PRIMARY KEY,
      vk_id int
);

CREATE TABLE prices (
      price_id INT AUTO_INCREMENT PRIMARY KEY,
      value int,
      url_ad char(300),
      url_req char(300),
      user_id int,
      FOREIGN KEY (user_id) REFERENCES vk_users(user_id)
);

CREATE TABLE avg_price (
    user_id INT,
    parse_date date,
    price float,
    url_req char(300),
    FOREIGN KEY (user_id) REFERENCES vk_users(user_id)
);

  */



/*медиана

  SET @row_number:=0;
  SET @median_group:='';

  SELECT
      median_group, AVG(value) AS median
  FROM
      (SELECT
          @row_number:=CASE
                  WHEN @median_group = user_id THEN @row_number + 1
                  ELSE 1
              END AS count_of_group,
              @median_group:=user_id AS median_group,
              user_id,
              value,
              (SELECT
                      COUNT(*)
                  FROM
                      prices
                  WHERE
                      a.user_id = user_id) AS total_of_group
      FROM
          (SELECT
          user_id, value
      FROM
          prices
      ORDER BY user_id , value) AS a) AS b
  WHERE
      count_of_group BETWEEN total_of_group / 2.0 AND total_of_group / 2.0 + 1
  GROUP BY median_group

  */

