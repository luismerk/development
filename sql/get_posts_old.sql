
SELECT 
	* 
FROM 
	`gcdlh_posts` as gp
WHERE
	EXISTS
    (
        SELECT
        	0
        FROM
        	`gcdlh_postmeta`
        WHERE
        	post_id = gp.ID
        	AND
        	meta_key = 'am_video'
        	AND
        	char_length(meta_value) > 0
    )
    AND
    NOT EXISTS
    (
        SELECT
        	0
        FROM
        	`gcdlh_postmeta`
        WHERE
        	post_id = gp.ID
        	AND
        	meta_key = 'am_video_cloud'
        	AND
        	char_length(meta_value) > 0
    )
    AND
    post_type = 'post'
ORDER BY `post_date` DESC  
