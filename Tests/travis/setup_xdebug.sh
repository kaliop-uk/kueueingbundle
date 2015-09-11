#!/bin/sh

# pecl for now only has xdebug 2.3.2 available
#pecl channel-update pecl.php.net
#pecl install xdebug

wget http://xdebug.org/files/xdebug-2.3.3.tgz
tar -xzf xdebug-2.3.3.tgz
cd xdebug-2.3.3
./configure --enable-xdebug
make
make install
