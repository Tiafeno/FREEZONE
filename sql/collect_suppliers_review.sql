SELECT SQL_CALC_FOUND_ROWS
    *
FROM
    free_users AS users
WHERE
    users.ID IN (SELECT 
            CAST(pm2.meta_value AS SIGNED)
        FROM
            free_posts AS pts
                JOIN
            free_postmeta AS pm ON (pm.post_id = pts.ID)
                JOIN
            free_postmeta AS pm2 ON (pm2.post_id = pts.ID)
                JOIN
            free_postmeta AS pm3 ON (pm3.post_id = pts.ID)
        WHERE
            pm.meta_key = 'date_review'
                AND TIMESTAMPADD(HOUR, 24, pm.meta_value) < CAST('2019-10-19 13:22:00' AS DATETIME)
			AND pm2.meta_key = 'user_id'
			AND (pm3.meta_key = 'product_id'
			AND CAST(pm3.meta_value AS SIGNED) IN ('1334'))
			AND pts.post_type = 'fz_product'
			AND pts.post_status = 'publish'
        GROUP BY pm.meta_value
        HAVING COUNT(*) > 0);
        
        
SELECT SQL_CALC_FOUND_ROWS
    *
FROM
    free_users AS users
WHERE
    users.ID IN (SELECT 
            CAST(pm2.meta_value AS SIGNED)
        FROM
            free_posts AS pts
                JOIN
            free_postmeta AS pm ON (pm.post_id = pts.ID)
                JOIN
            free_postmeta AS pm2 ON (pm2.post_id = pts.ID)
                JOIN
            free_postmeta AS pm3 ON (pm3.post_id = pts.ID)
        WHERE
            pm.meta_key = 'date_review'
                AND CAST(pm.meta_value AS DATETIME) < CAST('2019-10-19 06:00:00' AS DATETIME)
                AND pm2.meta_key = 'user_id'
                AND (pm3.meta_key = 'product_id'
                AND CAST(pm3.meta_value AS SIGNED) IN ('1334'))
                AND pts.post_type = 'fz_product'
                AND pts.post_status = 'publish'
        GROUP BY pm.meta_value
        HAVING COUNT(*) > 0)