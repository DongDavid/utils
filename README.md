<h1 align="center"> utils </h1>

<p align="center"> 工具类库.</p>


## Installing

```shell
$ composer require dongdavid/utils -vvv
```

## Require  

* ext-imageMagick

## Usage

```php
use dongdavid\Utils\Poster;

$poster = new Poster();
// 开启严格模式 默认开启，开启后图片或文字不能超出底图，超出会抛出错误
$poster->setStrict(true);

// 生成圆形头像

$poster->pic('./pic.png',Poster::PIC_CIRCULAR)->writeImage('./save.png');

// 生成二维码 
// 有logo
$poster->setQrcode('https://www.dongdavid.com','./save.png','./logo.png');
// 无logo
$poster->setQrcode('https://www.dongdavid.com','./save.png');

// 生成海报
// 添加图片的起始坐标是图片的左上角坐标
// 添加文字的起始坐标是文字的左下角坐标，不论文字多大多粗，其左下角坐标都不会变
        // 设置背景图片
$poster->setImgBackground('./beijing.png')
        // 向海报上添加图片good_img 起始坐标为 (0,44) 添加的图片长宽为 600,800
        ->addImage('./good_img.jpeg', 0, 44, 600, 800)
        // 向海报上添加图片good_img 起始坐标为 (15.5,875) 添加的图片长宽为 72,72
        ->addImage('./user_pic.png', 15.5, 875, 72, 72)
        // 写入文字 包括中文 自带了字体文件 PingFang-SC-Bold.ttf
        // ->setFont('./xxx.ttf')
        ->addText('自带字体文件能写入中文',50,800,20,'black')
        // 添加线条， 如划线价
        ->addLine(0,0,30,30)
        // 保存海报到本地
        ->save('./poster.png');

// 根据限定长度、字体大小，将文本内容拆分为多行
$line = $poster->setEmptyBackground(1000, 900)
               ->splitText('输入的文本内容oajdewo239023嗷嗷',16,300);

// 获取文本在图片中的长宽
$line = $poster->setEmptyBackground(1000, 900)
               ->getTextInfo('文本内容',32);

```

## Test  

```sh
#本地调试
git clone https://github.com/DongDavid/utils
mkdir utils-test
cd utils-test
composer init  
composer config repositories.utils path ../utils  
composer require dongdavid/utils:dev-master
touch index.php
```


## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/dongdavid/utils/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/dongdavid/utils/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## License

MIT