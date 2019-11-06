SELECT SQL_CALC_FOUND_ROWS * FROM free_posts as pts
WHERE pts.post_type = "fz_product" AND pts.post_status = "publish"
AND pts.ID IN (SELECT post_id FROM free_postmeta WHERE meta_key = 'user_id' AND meta_value = 20)
AND pts.ID IN (SELECT post_id FROM free_postmeta WHERE meta_key = 'product_id' AND meta_value IN (1334)) 
