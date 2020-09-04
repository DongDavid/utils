<?php

namespace Dongdavid\Utils;

class Poster
{
    /**
     * @var int 圆形.
     */
    const PIC_CIRCULAR = 1;
    /**
     * @var int 圆角.
     */
    const PIC_RADIUS = 2;
    /**
     * @var int 圆角边框.
     */
    const PIC_RADIUS_BORDER = 3;
    /**
     * @var int 边框 - 没做.
     */
    const PIC_BORDER = 4;
    /**
     * @var bool 严格模式， 图片和文字不能超出底图,否则会抛出异常.
     */
    private $strict = true;

    /**
     * @var \Imagick 海报对象
     */
    private $canvas;

    /**
     * @var array 海报尺寸类型
     */
    private $posterInfo = [
        'width' => 1064,
        'height' => 600,
        'type' => 'png',
    ];
    /**
     * @var string 字体文件路径
     */
    private $font = __DIR__ . '/assets/PingFang-SC-Bold.ttf';

    /**
     * @param string｜Imagick $background 背景图片
     */
    public function __construct($background = '')
    {
        if (!empty($background)) {
            $this->setImgBackground($background);
        }
    }

    /**
     * @param bool $strict 是否开启严格模式
     * @return Poster $this
     */
    public function setStrict($strict)
    {
        $this->strict = $strict ? true : false;

        return $this;
    }

    /**
     * 设置海报分辨率 不建议设置
     *
     * @param $x float 分辨率x
     * @param $y float 分辨率y
     * @return Poster $this
     */
    public function setResolution($x, $y)
    {
        $this->canvas->setResolution($x, $y);
        return $this;
    }

    /**
     * 设置海报底图尺寸.
     *
     * @param int $width 宽度
     * @param int $height 长度
     * @return $this
     */
    public function setPosterGeometry($width, $height)
    {
        $this->posterInfo['width'] = $width;
        $this->posterInfo['height'] = $height;
        $this->canvas->scaleimage($width, $height);

        return $this;
    }

    /**
     * 设置字体文件路径.
     *
     * @param string $font 设置字体文件路径
     * @return Poster $this
     */
    public function setFont(string $font)
    {
        if (!file_exists($font)) {
            throw new \Exception('文件不存在:' . $font);
        }
        $this->font = $font;

        return $this;
    }

    /**
     * 设置海报格式.
     *
     * @param string $type png|jpeg|...
     * @return Poster $this
     */
    public function setType($type)
    {
        $this->posterInfo['type'] = $type;

        return $this;
    }

    /**
     * 设置底图背景.
     *
     * @param string|Imagick $image 海报底图
     * @return Poster $this
     */
    public function setImgBackground($image)
    {
        $this->canvas = $this->getImagick($image);
        $info = $this->canvas->getImageGeometry();
        $this->posterInfo['width'] = $info['width'];
        $this->posterInfo['height'] = $info['height'];

        return $this;
    }

    /**
     * 设置空画布作为背景.
     *
     * @param int    $width 底图宽度
     * @param int    $height 底图长度
     * @param string $color 底图颜色 none表示透明
     * @return Poster $this
     */
    public function setEmptyBackground(int $width, int $height, string $color = 'none')
    {
        $this->canvas = new \Imagick();
        $this->canvas->newImage($width, $height, new \ImagickPixel($color));
        $this->posterInfo['width'] = $width;
        $this->posterInfo['height'] = $height;
        return $this;
    }

    /**
     * 合并图片到底图.
     *
     * @param string|Imagick $pic 要合并的图片 支持二进制字符串、本地文件路径、远程图片uri、Imagick对象
     * @param int            $x 图片的起始位置X坐标
     * @param int            $y 图片的起始位置y坐标
     * @param int            $width 图片的最终宽度
     * @param int            $height 图片的最终高度
     * @return Poster $this
     * @throws \ImagickException
     */
    public function addImage($pic, int $x, int $y, int $width = 0, int $height = 0)
    {
        if ($x < 0) {
            throw new \Exception('x不能小于0');
        }

        if ($y < 0) {
            throw new \Exception('x不能小于0');
        }

        if ($this->strict && $x + $width > $this->posterInfo['width']) {
            throw new \Exception('添加图片时图片不能超出底图');
        }
        if ($this->strict && $y + $height > $this->posterInfo['height']) {
            throw new \Exception('添加图片时图片不能超出底图');
        }

        $img = $this->getImagick($pic);
        $info = $img->getImageGeometry();
        $p_w = $width > 0 ? $width : $info['width'];
        $p_h = $height > 0 ? $height : $info['height'];

        // 缩放大小到目标尺寸
        if ($p_w != $info['width'] || $p_h != $info['height']) {
            $img->scaleimage($p_w, $p_h);
        }

        $this->canvas->compositeImage($img, \Imagick::COMPOSITE_OVER, $x, $y);
        $img->destroy();

        return $this;
    }

    /**
     * 添加文字到底图.
     *
     * @param string $text 文字内容
     * @param int    $x 文字的起始位置x坐标
     * @param int    $y 文字的起始位置y坐标
     * @param int    $fontSize 文字的大小
     * @param string $fontColor 文字的颜色 支持rgb  'white'|'rgb(255,255,255)'
     * @param int    $fontWeight 文字粗细 取值范围100-500
     * @return Poster $this
     * @throws \Exception
     */
    public function addText(string $text, int $x, int $y, int $fontSize = 16, $fontColor = 'black', int $fontWeight = 0)
    {
        $draw = new \ImagickDraw();

        $draw->setFont($this->font);

        $draw->setFillColor(new \ImagickPixel($fontColor));

        $draw->setFontSize($fontSize);

        if ($fontWeight > 0) {
            $draw->setFontWeight($fontWeight);
        }

        $metrix = $this->canvas->queryFontMetrics($draw, $text);

        if ($this->strict && $x + $metrix['textWidth'] > $this->posterInfo['width']) {
            throw new \Exception('文字长度不能超过底图');
        }
        if ($this->strict
            && $y > $this->posterInfo['height'] - 5
            && $y - $metrix['textHeight'] > 0
        ) {
            throw new \Exception('文字高度或竖直方向不能超出底图');
        }
        $draw->annotation($x, $y, $text);

        $this->canvas->drawImage($draw);
        $draw->destroy();

        return $this;
    }

    /**
     * 在底图上画线
     *
     * @param int    $x1 起点的x坐标
     * @param int    $y1 起点的y坐标
     * @param int    $x2 终点的x坐标
     * @param int    $y2 终点的y坐标
     * @param string $color 线的颜色 gray|rgb(255,255,255)
     * @param int    $fontSize 线的粗细
     * @return Poster $this
     */
    public function addLine(int $x1, int $y1, int $x2, int $y2, string $color = 'gray', int $fontSize = 20)
    {
        $draw = new \ImagickDraw();
        // $draw->setStrokeColor($strokeColor);
        $draw->setFillColor($color);
        // $draw->setStrokeWidth(2);
        $draw->setFontSize($fontSize);
        $draw->line($x1, $y1, $x2, $y2);
        $this->canvas->drawImage($draw);
        $draw->destroy();

        return $this;
    }

    /**
     * 保存海报到文件.
     *
     * @param string $filename 海报保存路径
     * @return bool|string 传入保存路径为空则返回图片二进制字符串
     */
    public function save(string $filename = '')
    {
        $this->canvas->setFormat($this->posterInfo['type']);
        if (!empty($filename)) {
            $this->canvas->writeImage($filename);
            $this->canvas->destroy();
            return true;
        }
        $blob = $this->canvas->getImageBlob();
        $this->canvas->destroy();
        return $blob;
    }

    /**
     * 将一个Imagick对象保存到文件.
     *
     * @param stirng  $filename 图片保存路径
     * @param Imagick $imagick imagick对象
     * @param string  $type 图片类型 png|jpeg
     */
    public function saveImagick(string $filename, \Imagick $imagick, string $type = 'png')
    {
        $imagick->setFormat($type);
        $imagick->writeImage($filename);
        $imagick->destroy();
    }

    /**
     * 获取imagick对象
     *
     * @param string|Imagick $obj 本地图片路径、远程图片链接、二进制图片字符串、Imagick对象
     * @return \Imagick
     */
    public function getImagick($obj)
    {
        if ($obj instanceof \Imagick) {
            return $obj;
        }

        if (file_exists($obj)) {
            return $this->getLocalImg($obj);
        }
        if (false !== strpos($obj, 'http')) {
            return $this->getRemoteImg($obj);
        }

        return $this->getBlobImg($obj);
    }

    /**
     * 头像处理.
     *
     * @param string|\Imagick $pic 需要处理的图片
     * @param int             $type 处理类型
     * @param array           $option 额外参数
     * @return Imagick
     * @throws \Exception
     */
    public function pic($pic, int $type = 0, array $option = [])
    {
        $pic = $this->getImagick($pic);
        $pic->setFormat('png');
        switch ($type) {
            case self::PIC_CIRCULAR:
                return $this->circular($pic);
            case self::PIC_RADIUS_BORDER:
                return $this->radius_border($pic, $option);
            case self::PIC_RADIUS:
                return $this->radius($pic, $option);
            case 0:
                return $pic;
            default:
                throw new \Exception('无效的参数');
        }
    }

    /**
     * 生成圆形头像.
     *
     * @param \Imagick $img 图片
     * @return \Imagick
     */
    public function circular($img)
    {
        $i = $img->getImageGeometry();
        $radius = $i['width'] / 2;
        $img->roundCorners($radius, $radius);

        return $img;
    }

    /**
     * 生成圆角头像.
     *
     * @param \Imagick $img 图片
     * @param array    $option 参数
     * @param int      $option ['radius'] 圆角比例 默认0。2
     * @return \Imagick
     */
    public function radius($img, array $option)
    {
        $radius = isset($option['radius']) ? $option['radius'] : 0.2;
        $i = $img->getImageGeometry();
        $radius = $i['width'] * $radius;
        $img->roundCorners($radius, $radius);

        return $img;
    }

    /**
     * 生成圆角边框头像.
     *
     * @param \Imagick $img 图片
     * @param array    $option 参数
     * @param int      $option ['radius']      圆角比例 默认0。2
     * @param int      $option ['borderWidth'] 边框宽度 默认 2
     * @param string   $option ['borderColor'] 边框颜色 默认 white
     * @return \Imagick
     */
    public function radius_border($img, array $option)
    {
        $radius = isset($option['radius']) ? $option['radius'] : 0.2;
        $borderColor = isset($option['borderColor']) ? $option['borderColor'] : 'white';
        $borderWidth = isset($option['borderWidth']) ? $option['borderWidth'] : 2;

        $img->setImageFormat('png');
        $i = $img->getImageGeometry();
        $radius1 = $i['width'] * $radius;
        $img->roundCorners($radius1, $radius1);
        $canvas = new \Imagick();
        $canvas_w = $i['width'] + $borderWidth * 2;
        $canvas->newImage($canvas_w, $canvas_w, $borderColor, 'png');

        $canvas->compositeImage($img, \Imagick::COMPOSITE_OVER, $borderWidth, $borderWidth);

        $radius2 = $canvas_w * $radius;
        $canvas->roundCorners($radius2, $radius2);
        $img->destroy();

        return $canvas;
    }

    /**
     * 获取图片的长宽.
     *
     * @param string|\Imagic $img
     * @return array ['width'=>32,'height'=>32]
     */
    public function getInfo($img)
    {
        $img = $this->getImagick($img);
        $info = $img->getImageGeometry();
        $img->destroy();

        return $info;
    }

    /**
     * 获取文本在图片中所占的长宽.
     *
     * @param string $text 文本内容
     * @param int    $fontSize 字体大小
     * @return array $metrix 长宽
     */
    public function getTextInfo(string $text, int $fontSize = 16)
    {
        $draw = new \ImagickDraw();

        $draw->setFont($this->font);

        $draw->setFontSize($fontSize);

        $metrix = $this->canvas->queryFontMetrics($draw, $text);
        $draw->destroy();

        return $metrix;
    }

    /**
     * 将字符串根据长度拆分为多行. 字体粗细不影响文字的长宽
     *
     * @param string $text 文本内容
     * @param int    $fontSize 字体大小
     * @param int    $maxWidth 单行最大宽度 默认为海报宽度
     * @return array[] 返回的是一个二维数组
     */
    public function splitText(string $text, int $fontSize = 16, int $maxWidth = 0)
    {
        $draw = new \ImagickDraw();

        $draw->setFont($this->font);

        $draw->setFontSize($fontSize);

        if ($maxWidth == 0) {
            $maxWidth = $this->posterInfo['width'];
        }

        $metrix = $this->canvas->queryFontMetrics($draw, $text);
        if ($metrix['textWidth'] < $maxWidth) {
            return [
                [
                    'text' => $text,
                    'width' => $metrix['textWidth'],
                    'height' => $metrix['textHeight']
                ],
            ];
        }
        $line = [];
        $tmp = [
            'text' => '',
            'width' => 0,
            'height' => 0,
        ];
        $words = preg_split("//u", $text, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($words as $word) {
            $info = $this->canvas->queryFontMetrics($draw, $tmp['text'] . $word);
            if ($info['textWidth'] >= $maxWidth) {
                $line[] = $tmp;
                $tmp = [
                    'text' => $word,
                    'width' => 0,
                    'height' => 0,
                ];
            } else {
                $tmp['text'] .= $word;
                $tmp['width'] = $info['textWidth'];
                $tmp['height'] = $info['textHeight'];
            }
        }
        $line[] = $tmp;
        return $line;
    }

    /**
     * 从base64格式字符串中获取图片.
     */
    // public function getBase64Img($base64)
    // {
    //     $img = new \Imagick();
    //     $img->readImageBlob(base64_decode($base64));
    //     return $img;
    // }

    /**
     * 从二进制字符串获取图片.
     *
     * @param string $data 图片二进制字符串
     * @return \Imagick
     */
    public function getBlobImg(string $data)
    {
        $img = new \Imagick();
        $img->readImageBlob($data);

        return $img;
    }

    /**
     * 从本地获取图片.
     *
     * @param string $path 本地图片路径
     * @return \Imagick
     */
    public function getLocalImg(string $path)
    {
        $img = new \Imagick($path);

        return $img;
    }

    /**
     * 下载远程图片到本地.
     *
     * @param string $url 远程图片链接
     * @return \Imagick
     */
    public function getRemoteImg(string $url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $file_content = curl_exec($ch);
        curl_close($ch);
        $img = new \Imagick();
        $img->readImageBlob($file_content);

        return $img;
    }

    /**
     * 生成二维码 默认二维码大小为330x330 ，logo大小为100x100.
     *
     * @param string $url 二维码链接
     * @param string $filename 二维码保存路径
     * @param string $logo_path 二维码logo
     * @return string  若传入的保存路径为空，则返回二维码图片的二进制字符串
     */
    public function setQrcode(string $url, string $filename, string $logo_path = '')
    {
        $qrCode = new \Endroid\QrCode\QrCode($url);
        $qrCode->setEncoding('UTF-8');
        $qrCode->setErrorCorrectionLevel(\Endroid\QrCode\ErrorCorrectionLevel::HIGH());
        $qrCode->setSize(330);
        $qrCode->setMargin(10);
        $qrCode->setLogoPath($logo_path);
        $qrCode->setLogoSize(100, 100);
        if (empty($filename)) {
            return $qrCode->writeString();
        }
        $qrCode->writeFile($filename);

        // $img = new \Imagick();
        // $img->readImageBlob($qrCode->writeString());
        // $qrCode = null;
        // $i = $img->getImageGeometry();
        // $logo = new \Imagick($logo_path);
        // $logo_w = round($i['width'] / 3.3);
        // $logo->scaleimage($logo_w,$logo_w);
        // $img->compositeImage($logo,\Imagick::COMPOSITE_OVER,($i['width'] - $logo_w) / 2, ($i['width'] - $logo_w) / 2);
        // $img->writeImage($filename);
    }
}
