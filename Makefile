

strip:
	find . -type d -path "*/lang/*" | grep -v -e '/zh$$' -e '/en$$' -e ".htaccess" | xargs rm -rf
