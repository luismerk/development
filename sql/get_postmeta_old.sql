

SELECT DISTINCT
	meta_key 
FROM  
	`gcdlh_postmeta`
WHERE
	meta_key LIKE '%am_%'
order by meta_key
