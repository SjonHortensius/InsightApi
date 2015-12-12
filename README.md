# InsightApi

I really like the [Insight interface](https://github.com/bitpay/insight-ui) Bitpay created but wanted to run this using
BitcoinXt instead of Bitpays node-based bitcoin daemon; which is why I started this project; it intends to implement a compatible
API.

To use it, prepare an fpm vhost, deploy the insight-ui and this API, then symlink this API into the UI:

```bash

git clone https://github.com/bitpay/insight-ui
git clone https://github.com/SjonHortensius/InsightApi
ln -s ../InsightApi/ insight-ui/api/
```
