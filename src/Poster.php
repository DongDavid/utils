<?php
namespace Dongdavid\Utils;

class Poster
{

    const PIC_CIRCULAR      = 1; // 圆形
    const PIC_RADIUS        = 2;
    const PIC_RADIUS_BORDER = 3;
    const PIC_BORDER        = 4;

    private $strict = true;
    // 底图
    private $canvas;
    // 海报尺寸
    private $posterInfo = [
        'width'  => 1136,
        'height' => 640,
        'type'   => 'png',
    ];
    // 字体文件路径
    private $font = __DIR__ . '/assets/PingFang-SC-Bold.ttf';

    public function __construct($background = '')
    {
        if (!empty($background)) {
            $this->setImgBackground($background);
        }
    }

    public function setStrict($strict)
    {
        $this->strict = $strict ? true : false;
        return $this;
    }
    // 设置海报尺寸
    public function setPosterGeometry($width, $height)
    {
        $this->posterInfo['width']  = $width;
        $this->posterInfo['height'] = $height;

        return $this;
    }

    /**
     * 设置字体文件路径
     */
    public function setFont(string $font)
    {
        if (!file_exists($font)) {
            throw new \Exception('文件不存在:' . $font);
        }
        $this->font = $font;
        return $this;
    }

    public function setType($type)
    {
        $this->posterInfo['type'] = $type;
        return $this;
    }

    /**
     * 设置底图背景
     */
    public function setImgBackground($image)
    {
        $this->canvas               = $this->getImagick($image);
        $info                       = $this->canvas->getImageGeometry();
        $this->posterInfo['width']  = $info['width'];
        $this->posterInfo['height'] = $info['height'];
        return $this;
    }

    /**
     * 设置空画布作为背景
     */
    public function setEmptyBackground(int $width, int $height, string $color = 'none')
    {
        $this->canvas = new \Imagick();
        $this->canvas->newImage($width, $height, new \ImagickPixel($color));
        $this->posterInfo['width']  = $width;
        $this->posterInfo['height'] = $height;
    }

    /**
     * 合并图片
     */
    public function addImage(string $pic, int $x, int $y, int $width = 0, int $height = 0)
    {
        if ($x < 0) {
            throw new \Exception("x不能小于0");
        }

        if ($y < 0) {
            throw new \Exception("x不能小于0");
        }

        if ($this->strict && $x + $width > $this->posterInfo['width']) {
            throw new \Exception("添加图片时图片不能超出底图");
        }
        if ($this->strict && $y + $height > $this->posterInfo['height']) {
            throw new \Exception("添加图片时图片不能超出底图");
        }

        $img  = new \Imagick($pic);
        $info = $img->getImageGeometry();
        $p_w  = $width > 0 ? $width : $info['width'];
        $p_h  = $height > 0 ? $height : $info['height'];

        // 缩放大小到目标尺寸
        if ($p_w != $info['width'] || $p_h != $info['height']) {
            $img->scaleimage($p_w, $p_h);
        }

        $this->canvas->compositeImage($img, \imagick::COMPOSITE_OVER, $x, $y);
        return $this;
    }


    /**
     * 写入文本
     */
    public function addText($text, $x, $y, $fontSize = 16, $fontColor = 'black',int $fontWeight = 0)
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
            throw new \Exception("文字长度不能超过底图");
        }
        if ($this->strict
            && $y > $this->posterInfo['height'] - 5
            && $y - $metrix['textHeight'] < $this->posterInfo['height']
        ) {
            throw new \Exception("文字高度或竖直方向不能超出底图");
        }
        $draw->annotation($x, $y, $text);

        $this->canvas->drawImage($draw);
        $draw->destory();
        return $this;
    }

    /**
     * 画线
     */
    public function addLine($x1,$y1,$x2,$y2,$color = 'gray',$fontSize = 20)
    {
        $draw = new \ImagickDraw();

        // $draw->setStrokeColor($strokeColor);
        $draw->setFillColor($color);

        // $draw->setStrokeWidth(2);
        $draw->setFontSize($fontSize);

        $draw->line($x1, $y1, $x2, $y2);
        $this->canvas->drawImage($draw);
        $draw->destory();
        return $this;
    }
    /**
     * 导出到文件
     */
    public function save($filename)
    {
        $this->canvas->setFormat($this->posterInfo['type']);
        $this->canvas->writeImage($filename);
        $this->canvas->destory();
    }

    public function saveImagick($filename,$imagick,$type = 'png')
    {
        $imagick->setFormat($type);
        $imagick->writeImage($filename);
        $imagick->destory();
    }
    /**
     * 获取imagick对象
     */
    public function getImagick($obj)
    {
        if ($obj instanceof \Imagick) {
            return $obj;
        }
        if (!file_exists($obj)) {
            throw new \Exception('文件不存在:' . $obj);
        }
        return new \Imagick($obj);
    }

    public function checkType($obj)
    {
        if ($obj instanceof \Imagick) {
            return true;
        }
        return false;
    }
    /**
      * 头像处理
      */
    public function pic($pic, int $type = 0, array $option = [])
    {

        $pic = $this->getImagick($pic);
        switch ($type) {
            case self::PIC_CIRCULAR:
                return $this->circular($pic);
            case self::PIC_RADIUS_BORDER:
                return $this->radius_border($pic, $option);
            case self::PIC_RADIUS:
                return $this->radius($pic, $option);
            default:
                return $img;
                break;
        }
    }

    /**
     * 生成圆形头像
     */
    public function circular($img)
    {
        $i      = $img->getImageGeometry();
        $radius = $i['width'] / 2;
        $img->roundCorners($radius, $radius);
        return $img;
    }

    /**
     * 生成圆角头像
     */
    public function radius($img, $option)
    {
        $radius = isset($option['radius']) ? $option['radius'] : 0.2;
        $i      = $img->getImageGeometry();
        $radius = $i['width'] * $radius;
        $img->roundCorners($radius, $radius);
        return $img;
    }

    /**
     * 生成圆角边框头像
     */
    public function radius_border($img, $option)
    {
        if (isset($option['radius'])) {
            $radius = $option['radius'];
        } else {
            $radius = 0.2;
        }
        $radius      = isset($option['radius']) ? $option['radius'] : 0.2;
        $borderColor = isset($option['borderColor']) ? $option['borderColor'] : 'white';
        $borderWidth = isset($option['borderWidth']) ? $option['borderWidth'] : 2;

        $img->setImageFormat('png');
        $i       = $img->getImageGeometry();
        $radius1 = $i['width'] * $radius;
        $img->roundCorners($radius1, $radius1);
        $canvas = new Imagick();
        $canvas = $i['width'] + $borderWidth * 2;
        $canvas->newImage($canvas_w, $canvas_w, $borderColor, 'png');

        $canvas->compositeImage($img, \Imagick::COMPOSITE_OVER, $borderWidth, $borderWidth);

        $radius2 = $canvas_w * $radius;
        $canvas->roundCorners($radius2, $radius2);
        $img->destroy();
        return $canvas;
    }
    /**
     * 获取图片的长宽
     */
    public function getInfo($img)
    {
        $img = $this->getImagick($img);
        $info = $img->getImageGeometry();
        $img->destroy();
        return $info;
    }
    /**
     * 获取文本在图片中所占的长宽
     */
    public function getTextInfo($text, $fontSize = 16, $fontColor = 'black',int $fontWeight = 0)
    {
        $draw = new \ImagickDraw();

        $draw->setFont($this->font);

        $draw->setFillColor(new \ImagickPixel($fontColor));

        $draw->setFontSize($fontSize);

        if ($fontWeight > 0) {
            $draw->setFontWeight($fontWeight);
        }

        $metrix = $this->canvas->queryFontMetrics($draw, $text);
        $draw->destory();
        return $metrix;
    }
}