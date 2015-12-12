# InsightApi

I really like the [Insight interface](https://github.com/bitpay/insight-ui) Bitpay created but wanted to run this using
BitcoinXt instead of Bitpays node-based bitcoin daemon; which is why I started this project; it intends to implement a compatible
API.

To use it, prepare an fpm vhost, deploy the insight-ui and this API, then symlink this API into the UI:

```bash
git clone https://github.com/bitpay/insight-ui
mkdir insight-ui/socket.io/ && curl https://test-insight.bitpay.com/socket.io/socket.io.js -O insight-ui/socket.io/socket.io.js

git clone https://github.com/SjonHortensius/InsightApi
ln -s ../InsightApi/ insight-ui/public/api/
```

Then copy config-sample.php to config.php, and edit config.php with the correct bitcoin rpc credentials.
This is a WIP, we don't worry about security and performance yet, and some things will simply not work