# End-to-end tests

Running theses tests requires having [matrix-oidc-playground](https://github.com/Automattic/matrix-oidc-playground/) running in the same machine as the tests. Make sure to follow the setup instructions there before running the tests.

Once you have matrix-oidc-playground running, simply run:

```shell
composer test
```

The tests pass when the output ends with something like:


```shell
JWT token {
  iss: 'https://localhost:8443/',
  sub: 'admin',
  aud: 'oidc-server-plugin-tests',
  iat: 1695316090,
  exp: 1695319690,
  auth_time: 1695316090,
  nonce: '7926217c4ad37e6db5cc8e6f78a421ed'
}
userinfo {
  scope: 'openid profile',
  username: 'admin',
  name: 'admin',
  nickname: 'admin',
  picture: 'https://secure.gravatar.com/avatar/e64c7d89f26bd1972efa854d13d7dd61?s=96&d=mm&r=g',
  sub: 'admin'
}
```
