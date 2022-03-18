PHOTON is a project that makes simple way of viewing web galleries
using PhotoSwipe library from local file storage

## Installation
1. Create and configure `.env.local` file configs
3. Run
```shell
yarn install
yarn run dev
composer install
symfony run app:storage:link $sourcepath1
symfony run app:storage:link $sourcepath2 --target srcPath2
...
symfony run server:start
```

## Requirements
1. NodeJS v12
2. PHP v7
3. Yarn

## TODO
- [ ] specify sort pattern within folder. Custom, presets
- [ ] show thumbnails