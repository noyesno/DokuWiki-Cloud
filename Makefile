

strip:
	find . -type d -path "*/lang/*" | grep -v -e '/zh$$' -e '/en$$' | xargs rm -rf
