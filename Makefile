MIN_PHP_VER = 5.3.0
CUR_PHP_VER = ${shell php -r "echo PHP_VERSION;"}
PHP_BIN = ${shell which ruby;}

install:
	if [ "${PHP_BIN}" = "" ]; then \
		echo "moo"; \
	fi
moot:
	if [ ${MIN_PHP_VER} -lt ${CUR_PHP_VER} ] ; then \
		echo "Your php version needs to be 5.4 or higher"; \
	else \
		echo "Your php version is allright!"; \
	fi