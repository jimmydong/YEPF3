<?php
namespace ext;
//==========================================
// 文件: image.class.php
// 程序: 图片处理类
// 作者: huhongwei
// 时间: 2008-07-5
//==========================================
class Images{

	/**
	 * 生成缩略图
	 *
	 * @param unknown_type $img       源文件
	 * @param unknown_type $maxwidth  缩略图宽
	 * @param unknown_type $maxheight 缩略图高
	 * @param unknown_type $dstimg    生成的缩略图文件名[包含路径]
	 * @param Boolean $is_cut 是否剪裁
	 * @param Boolean $pmode 图片模式，是否保证约定尺寸输出图片（使用白色填充空白部分），0：不保证，1：保证；
	 */
	function imageResize($img,$maxwidth,$maxheight,$dstimg,$is_cut = false,$pmode=false)
	{
		if(empty( $img ) || !file_exists( $img )) return false;
		//根据不同的格式读取源图片
		list($width, $height, $pic_info) = @getimagesize($img);
		switch($pic_info)
		{
			case 1: $image = imagecreatefromgif($img);break;//GIF
			case 2: $image = imagecreatefromjpeg($img);break;//JPG
			case 3: $image = imagecreatefrompng($img);imagesavealpha($image, true);break;//PNG
			case 6: $image = imagecreatefromwbmp($img);break;//BMP
			default:return false;
		}

		//计算成比例的宽高
		if($maxwidth && $width > $maxwidth)
		{
			$widthratio = $maxwidth/$width;
			$RESIZEWIDTH=true;
		}
		if($maxheight && $height > $maxheight)
		{
			$heightratio = $maxheight/$height;
			$RESIZEHEIGHT=true;
		}

		$newwidth = $width;		// 新图片的宽度
		$newheight = $height;	// 新图片的高度
		$ratio = 1;				// 缩放比例
		$cut_side = 0;			// 剪裁的边，0：不需要；1：宽；2：高；
		$pos_x = 0;				// 偏移X
		$pos_y = 0;				// 偏移Y
		$target_width = $width;		// 原图的目标宽度
		$target_height = $height;	// 原图的目标高度

		if ($is_cut == false)
		{	// 不剪裁，保持原图的完整性
			if($RESIZEWIDTH && $RESIZEHEIGHT)
			{
				$ratio = min($widthratio, $heightratio);
			}
			elseif($RESIZEWIDTH)
			{
				$ratio = $widthratio;
			}
			elseif($RESIZEHEIGHT)
			{
				$ratio = $heightratio;
			}
			else
			{
				$ratio = 1;
			}
			$newwidth = $width * $ratio;
			$newheight = $height * $ratio;
		}
		else
		{	// 剪裁原图，保证目标图片尺寸的完整性
			if($RESIZEWIDTH && $RESIZEHEIGHT)
			{
				if ($widthratio > $heightratio)
				{
					$ratio = $widthratio;
					$newwidth = $width * $widthratio;
					$newheight = $maxheight;
					$cut_side = 2;
				}
				else
				{
					$ratio = $heightratio;
					$newwidth = $maxwidth;
					$newheight = $height * $heightratio;
					$cut_side = 1;
				}
			}
			elseif($RESIZEWIDTH)
			{
				$newwidth = $maxwidth;
				$newheight = min($maxheight, $height);
				$cut_side = 1;
			}
			elseif($RESIZEHEIGHT)
			{
				$newwidth = min($maxwidth, $width);
				$newheight = $maxheight;
				$cut_side = 2;
			}

			if ($cut_side == 1)
			{	// 剪切图片的宽
				$target_width = $newwidth / $ratio;
				$pos_x = ($width - $target_width) / 2;
			}
			else if ($cut_side == 2)
			{	// 剪切图片的高
				$target_height = $newheight / $ratio;
//				$pos_y = ($height - $target_height) / 2;
			}
		}

		if(function_exists("imagecopyresampled"))
		{
			if ($pmode)
			{
				$newim = imagecreatetruecolor($maxwidth, $maxheight);//新建一个真彩色图像[黑色图像]
				$white = imagecolorallocate($newim,255,255,255);
				imagefilledrectangle($newim,0,0,$maxwidth,$maxheight,$white);
				imagecolortransparent($newim,$white);
				imagecopyresampled($newim, $image, ($maxwidth-$newwidth)/2, ($maxheight-$newheight)/2, $pos_x, $pos_y, $newwidth, $newheight, $target_width, $target_height);//重采样拷贝部分图像并调整大小
			}
			else
			{
				$newim = imagecreatetruecolor($newwidth, $newheight);//新建一个真彩色图像[黑色图像]
				$white = imagecolorallocate($newim,255,255,255);
				imagefilledrectangle($newim,0,0,$newwidth,$newheight,$white);
				imagecolortransparent($newim,$white);
				imagecopyresampled($newim, $image, 0, 0, $pos_x, $pos_y, $newwidth, $newheight, $target_width, $target_height);//重采样拷贝部分图像并调整大小
			}
		}
		else
		{
			if ($pmode)
			{
				$newim = imagecreate($maxwidth, $maxheight);
				$white = imagecolorallocate($newim,255,255,255);
				imagefilledrectangle($newim,0,0,$maxwidth,$maxheight,$white);
				imagecolortransparent($newim,$white);
				imagecopyresized($newim, $image, ($maxwidth-$newwidth)/2, ($maxheight-$newheight)/2, $pos_x, $pos_y, $newwidth, $newheight, $target_width, $target_height);
			}
			else
			{
				$newim = imagecreate($newwidth, $newheight);
				$white = imagecolorallocate($newim,255,255,255);
				imagefilledrectangle($newim,0,0,$newwidth,$newheight,$white);
				imagecolortransparent($newim,$white);
				imagecopyresized($newim, $image, 0, 0, $pos_x, $pos_y, $newwidth, $newheight, $target_width, $target_height);
			}
		}

		switch($pic_info)
		{
			case 1: imagegif($newim,$dstimg);break;		//GIF
			case 2: imagejpeg($newim,$dstimg,90);break;	//JPG
			case 3: imagepng($newim,$dstimg);break;		//PNG
			default:return false;
		}
		ImageDestroy($newim);
	}

	/**
	 * 功能：图片加水印 (基于GD加水印)
	 * 参数：
	 *      $groundImage    背景图片，即需要加水印的图片，暂只支持GIF,JPG,PNG格式；
	 *      $waterImage     图片水印，即作为水印的图片，暂只支持GIF,JPG,PNG格式；
	 *      $newImage       新图片的名称[包含路径]
	 *      $minWidth  $minHeight    原图最大打水印的宽高限制
	 *      $waterPos       水印位置，有10种状态，0为随机位置；
	 *                        1为顶端居左，2为顶端居中，3为顶端居右；
	 *                        4为中部居左，5为中部居中，6为中部居右；
	 *                        7为底端居左，8为底端居中，9为底端居右，10为自定义位置；
	 *      $opacity        透明度 0为不使用透明度 0~100使用透明度(100是完全不透明)
	 *
	 *      若$imgagePath为空时，加水印后的图片的文件名和 $groundImage 一样。
	 */
	function imageWaterMark($groundImage ,$waterImage , $newImage='' ,$minWidth = 480 ,$minHeight = 360 ,$waterPos = 10 , $opacity = 0)
	{
		//读取水印图片
		if(empty( $waterImage ) || !file_exists($waterImage)) return false;
		list($water_w,$water_h,$water_type) = $water_info = @getimagesize($waterImage); //取得水印图片的宽、高、格式
		$water_s_w = $water_w;
		$water_s_h = $water_h;
		switch($water_type) //取得水印图片的格式
		{
			case 1 : $water_im = imagecreatefromgif ( $waterImage );break;
			case 2 : $water_im = imagecreatefromjpeg ( $waterImage );break;
			case 3 : $water_im = imagecreatefrompng ( $waterImage );imagesavealpha($water_im, true);break;
			default: return false;
		}
		imagesavealpha($water_im, true);

		//读取背景图片
		if(empty($groundImage) || !file_exists($groundImage)) return false;
		list($ground_w,$ground_h,$ground_type) = @getimagesize($groundImage); //取得背景图片的宽、高、格式
		switch($ground_type) //取得背景图片的格式
		{
			case 1 : $ground_im = imagecreatefromgif ( $groundImage );break;
			case 2 : $ground_im = imagecreatefromjpeg ( $groundImage );break;
			case 3 : $ground_im = imagecreatefrompng ( $groundImage );imagesavealpha($ground_im, true);break;
			default:return false;
		}

		//图片水印

		//暂时改为小于160的也打水印
		/*if(( $ground_w < 160 ) || ( $ground_h < 120 ))
		{
		//小于160x120的图片不打水印
		return false;
		}*/
		if( ( $ground_w <= $minWidth ) || ( $ground_h <= $minHeight ))
		{
			//等比重新生成新的水印图
			$ratio = min($ground_w/$minWidth, $ground_h/$minHeight);//计算水印的缩略比例
			$water_s_w = intval($water_w * $ratio);
			$water_s_h = intval($water_h * $ratio);

			/*生成新缩略水印图,不必要了
			 if(function_exists("imagecopyresampled"))
			 {
			 $newim = imagecreatetruecolor($water_s_w, $water_s_h);
			 imagesavealpha($newim, true);
			 imagecopyresampled($newim, $water_im, 0, 0, 0, 0, $water_s_w, $water_s_h, $water_w, $water_h);
			 }
			 else
			 {
			 $newim = imagecreate($newwidth, $newheight);
			 $TransparentColor = imagecolorclosest($water_im,255,255,255); //取得原图的颜色的索引值[否则背景是黑的]
			 imagefill($newim,0,0,$TransparentColor);
			 imagesavealpha($newim, true);
			 imagecopyresized($newim, $water_im, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
			 }
			 $dstimg = $imgagePath.'/watermark_s.png';
			 imagepng($newim,$dstimg);
			 $water_im = imagecreatefrompng($dstimg);
			 list($water_w,$water_h) = getimagesize($dstimg);*/
		}
		$water_s_w = empty($water_s_w)?$water_w:$water_s_w;
		$water_s_h = empty($water_s_h)?$water_h:$water_s_h;

		//水印位置
		switch( $waterPos ) {
			case 0 : //随机
				$posX = rand ( 0 ,( $ground_w - $water_s_w ));
				$posY = rand ( 0 ,( $ground_h - $water_s_w ));
				break;
			case 1 : //1为顶端居左
				$posX = 0 ;
				$posY = 0 ;
				break;
			case 2 : //2为顶端居中
				$posX = ( $ground_w - $water_s_w ) / 2 ;
				$posY = 0 ;
				break;
			case 3 : //3为顶端居右
				$posX = $ground_w - $water_s_w ;
				$posY = 0 ;
				break;
			case 4 : //4为中部居左
				$posX = 0 ;
				$posY = ( $ground_h - $water_s_w ) / 2 ;
				break;
			case 5 : //5为中部居中
				$posX = ( $ground_w - $water_s_w ) / 2 ;
				$posY = ( $ground_h - $water_s_h ) / 2 ;
				break;
			case 6 : //6为中部居右
				$posX = $ground_w - $water_s_w ;
				$posY = ( $ground_h - $water_s_h ) / 2 ;
				break;
			case 7 : //7为底端居左
				$posX = 0 ;
				$posY = $ground_h - $water_s_h ;
				break;
			case 8 : //8为底端居中
				$posX = ( $ground_w - $water_s_w ) / 2 ;
				$posY = $ground_h - $water_s_h ;
				break;
			case 9 : //9为底端居右
				$posX = $ground_w - $water_s_w ;
				$posY = $ground_h - $water_s_h ;
				break;
			case 10 : //自定义位置
				$posX = $ground_w - $water_s_w - $ground_w * 0.05;
				$posY = $ground_h - $water_s_h - $ground_h * 0.05;
				break;
			default: //随机
				$posX = rand ( 0 ,( $ground_w - $w ));
				$posY = rand ( 0 ,( $ground_h - $h ));
				break;
		}

		//图片加水印
		if($opacity == 0)
		{
			//imagecopy($ground_im , $water_im , $posX , $posY , 0 , 0 , $water_w , $water_h); //拷贝水印到目标文件
			imagecopyresampled($ground_im , $water_im , $posX , $posY , 0 , 0 ,$water_s_w,$water_s_h, $water_w , $water_h); //拷贝水印到目标文件

		}
		else
		{
			imagecopymerge($ground_im , $water_im , $posX , $posY , 0 , 0 , $water_w , $water_h ,50); //拷贝水印到目标文件
		}
		//生成水印后的图片
		$new_image = $groundImage;
		if(!empty($newImage))
		{
			$new_image = $newImage;
		}
		switch($ground_type) //取得背景图片的格式
		{
			case 1 : imagegif($ground_im , $new_image);break;
			case 2 : imagejpeg($ground_im , $new_image,90);break;
			case 3 : imagepng($ground_im , $new_image);break;
			default:return false;
		}
		//释放内存
		if(isset($water_im)) imagedestroy($water_im);
		unset($ground_info);
		imagedestroy($ground_im);
	}

	/**
	 * 加给图片加水印(基于imagick加水印)
	 *
	 * @param strimg $groundImage 要加水印地址
	 * @param string $waterImage 水印图片地址
	 * @param string $newImage    新图片名称[包含路径]
	 * @param int $minWidth 小于此值不加水印
	 * @param int $minHeight 小于此值不加水印
	 * @param int $waterPos 水印位置
	 * @param float $alpha 透明度	/// 如果是PNG透明背景水印，此处最后设置为1.0 by alfa@YOKA
	 * @return FALSE
	 */
	function imageWaterMark2($groundImage ,$waterImage , $newImage='' , $minWidth=100 ,$minHeight=100 ,$waterPos = 9 ,$alpha=0.9)
	{
		$bg_h = $bg_w = $water_h = $water_w = 0;
		//获取背景图的高，宽
		if(!is_file($groundImage) || empty($groundImage)) return false;
		$bg = new Imagick();
		$bg ->readImage($groundImage);
		$bg_h = $bg->getImageHeight();
		$bg_w = $bg->getImageWidth();

		//获取水印图的高，宽
		if(!is_file($waterImage) || empty($waterImage)) return false;
		$water = new Imagick($waterImage);
		$water_h = $water->getImageHeight();
		$water_w = $water->getImageWidth();

		//如果背景图的高宽小于水印图的高宽或指定的高和宽则不加水印
		if($bg_h < $minHeight || $bg_w < $minWidth || $bg_h < $water_h || $bg_w < $water_w )
		{
			return false;
		}
		//图片加水印
		$dw = new ImagickDraw();
		/// 如果是PNG透明图片，使用setImageOpacity函数后，将不在透明
		/// 因此调整一下，如果是不作透明处理， 则不使用此函数、
		/// alfa@YOKA 2010/01/14
		if($alpha != 1.0)
		{
			$water->setImageOpacity($alpha);//设置透明度
		}
		$dw -> setGravity($waterPos);//设置对齐方式 9为底部对
		//设置水印位置
		$posX = $bg_w * 0.05;
		$posY = $bg_h * 0.05;
		$dw -> composite($water->getImageCompose(),$posX,$posY,0,0,$water);//合成当前图像
		$bg -> drawImage($dw);
		$new_image = $groundImage;
		if(!empty($newImage))
		{
			$new_image = $newImage;
		}
		if(!$bg -> writeImage($new_image))
		{
			return false;
		}
	}

	/**
	 * 图片格式转换
	 *
	 * @param unknown_type $srcImg  源图片
	 * @param unknown_type $dstImg  转换后的图片[包含路径]
	 */
	function imageConvert($src_image,$dst_image)
	{
		if(empty($src_image) || !is_file($src_image)) return false;
		$thumb = new Imagick();
		$thumb->readImage($src_image);
		$thumb->writeImage($dst_image);
		$thumb->clear();
		$thumb->destroy();
	}

	/**
	 * @desc 缩放图片
	 * @param $scimg 图片源地址(全路径)
	 * @param $obimg 目标地址(全路径)
	 * @param $obw 目标宽度
	 * @param $obh 目标高度
	 * @param $holdatio 是否保持原图高宽比,默认进行缩放
	 * @param $flop 图片进行左右翻转
	 * @param $flip 图片进行上下翻转
	 * @return unknown_type true/false
	 * @author alfa@YOKA 2009-9-15
	 */
	function ImagickResize($scimg,$obimg,$obw,$obh,$holdatio=false,$flop=0,$flip=0)
	{
		if(empty( $scimg ) || !file_exists( $scimg )) return false;

		$thumb = new Imagick();
		$thumb ->readImage($scimg);
		if($holdatio)
		{
			$imgheight = $thumb->getImageHeight();
			$imgwidth = $thumb->getImageWidth();
			/// 源图片比目标图片要小
			if($imgwidth < $obw && $imgheight < $obh)
			{
				$hratio = $obh / $imgheight;
				$wratio = $obw / $imgwidth;
				$ratio = $hratio < $wratio ? $hratio : $wratio;
				$obh = $imgheight * $ratio;
				$obw = $imgwidth * $ratio;
				$holdatio = false;
			}
		}
		$thumb ->resizeImage($obw,$obh,Imagick::FILTER_UNDEFINED,1,$holdatio);
		if($flop == 1)
		{
			$thumb->flopImage();
		}
		if($flip == 1)
		{
			$thumb->flipImage();
		}

		/// 如果字节少于五个，则表明传递的是输出格式信息
		if(strlen($obimg) < 5)
		{
			/// 如果没有指定，就按原格式输出
			if(empty($obimg))
			{
				$obimg = $thumb->getImageFormat();
			}
			$thumb->setImageFormat($obimg);
			header("Content-Type: image/{$obimg}");
			echo $thumb->getImageBlob();
			$ret = true;
		}
		else
		{
			$ret = $thumb->writeImage($obimg);
			$ret = array(
                'w' => $thumb->getImageWidth(),
                'h' => $thumb->getImageHeight(),
			);
		}
		$thumb->destroy();
		return $ret;

	}
	/**
	 * @desc 合成图片
	 * @param $scimgArr 要合成的图片,数组
	 * @param $obimg 目标文件(全路径)
	 * @param $obwidth 目标宽
	 * @param $obheight 目标高
	 * @param $obbgcolor 目标背景色
	 * @return unknown_type true/false
	 * @author alfa@YOKA 2009-9-15
	 */
	function ImagickComposite($scimgArr,$obimg,$obwidth,$obheight,$obbgcolor='white')
	{
		/// 按order排序
		usort($scimgArr,array("Images","sortByOrder"));

		/// 计算合成图片的大小
		$minleft = 10000;
		$maxwidth = 0;
		$mintop = 10000;
		$maxheight = 0;
		foreach ($scimgArr as $scimg)
		{
           $rect = $scimg['rect'];
            /// 兼容老版本(没有rect属性)
            if(is_array($rect)){
	 			/// 使用 rect 来计算，这样 旋转也正确了。
	            $minleft = min($minleft, $rect['x1']);
	            $mintop = min($mintop, $rect['y1']);
				if(!empty($scimg['filename']))
				{
	                $maxwidth = max($maxwidth, $rect['x2']);
	                $maxheight = max($maxheight, $rect['y2']);
				}
            }else{
                $minleft = min($minleft,$scimg['x']);
	            $mintop = min($mintop,$scimg['y']);
	            if(!empty($scimg['filename']))
	            {
	                $maxwidth = max($maxwidth,$scimg['x'] + $scimg['w']);
	                $maxheight = max($maxheight,$scimg['y'] + $scimg['h']);
	            }
            }
		}
		//		$offsetx = $maxwidth - $minleft;
		//		$offsety = $maxheight - $mintop;

		$obmaxwidth = $maxwidth - $minleft;
		$obmaxheight = $maxheight - $mintop;

		//		$this->ImagickCompositeFormatSize($scimgArr,$obwidth,$obheight);

		$layer1  = new Imagick();
		/// 右、下边  缺像素，增加2px
		$layer1->newImage($maxwidth - $minleft + 2, $maxheight - $mintop + 2, $obbgcolor);
		$layer1->setImageOpacity(1);

		foreach ($scimgArr as $scimg)
		{
			if(is_array($scimg['rect'])){
				/// 使用 rect 来计算
	            $scimg['x'] = $scimg['rect']['x1'];
	            $scimg['y'] = $scimg['rect']['y1'];
			}
			/// 要添加文字
			if(empty($scimg['filename']))
			{
				$dw = new ImagickDraw();
				$dw->setFont($scimg['font']);
				$dw->setFontSize($scimg['fontsize']);
				$dw->setFillColor(new ImagickPixel($scimg['color']));
				$layer1->annotateImage($dw,$scimg['x'] ,$scimg['y'] ,0,$scimg['text']);
				$dw->destroy();
			}
			else
			{
				$imk = new Imagick();
				$imk->readImage($scimg['filename']);
				/*
				 * 这个不能设置，否则会有一个大黑边，md，调这东西，花了这么长时间。。。
				 * alfa@YOKA
				 */
				///				$imk->setImageOpacity($scimg['alpha']);
				$imk->resizeImage($scimg['w'],$scimg['h'],Imagick::FILTER_UNDEFINED,1,$scimg['holdratio']);

				/// 增加1px透明边框，为了使旋转产生的偏移部分具有白背景
				/// ImagickPixel 颜色格式 RGBA 最后两个 0 表示透明，
				/// 配合 compositeImage + CHANNEL_ALPHA 通道 , 使得旋转偏移超出部分为透明
                $imk->borderImage(new ImagickPixel('#FFFFFF00'), 1, 1);
                /// 为适应 affine ， 为 transform数组，增加两个0
                array_push($scimg['transform'], 0, 0);
                /// 应用Matrix 旋转
                $imk->distortImage(IMAGICK::DISTORTION_AFFINEPROJECTION, $scimg['transform'], true);
                /* 原来的左右上下翻转功能，就被上面的 affine 代替了*/
					if($scimg['flop'] == 1)
					{
						$imk->flopImage();
					}
					if($scimg['flip'] == 1)
					{
						$imk->flipImage();
					}
				/**/

				/*
				 $imk->borderImage(new ImagickPixel('white'),1,1);
				 $imk->paintfloodfillimage('transparent',2000,NULL,0,0);
				 $dw  = new ImagickDraw();
				 $dw->color(0,0,Imagick::PAINT_FLOODFILL);
				 $imk->drawImage($dw);
				 $imk->shaveImage(1,1);
				 */
				$layer1->compositeImage($imk,$imk->getImageCompose(),$scimg['x'] - $minleft,$scimg['y'] - $mintop, Imagick::CHANNEL_ALPHA);
				$imk->destroy();

			}
		}

		$layer1->resizeImage($obwidth,$obheight,Imagick::FILTER_UNDEFINED,1,true);
		$layer2 = new Imagick();
		$layer2 -> newImage($obwidth,$obheight,$obbgcolor);

		$x = ($obwidth - $layer1->getImageWidth()) / 2;
		$y = ($obheight - $layer1->getImageHeight()) / 2;
		$layer2 ->compositeImage($layer1,imagick::COMPOSITE_OVER,$x,$y);
		/// 如果字节少于五个，则表明传递的是输出格式信息
		if(strlen($obimg) < 5)
		{
			/// 如果没有指定，就按原格式输出
			if(empty($obimg))
			{
				$obimg = "GIF";//$layer1->getImageFormat();
			}
			header("Content-Type: image/{$obimg}");
			$layer2->setImageFormat($obimg);
			echo $layer2->getImageBlob();
			$ret = true;
		}
		else
		{
			$ret = $layer2->writeImage($obimg);
		}

		///		$layer1->writeImage($obimg);
		$layer2->destroy();
		return $ret;

	}
	/**
	 * @desc 计算合成图片的原始宽高，应晓东的要求，把这个先提取出来
	 * @param $scimgArr 要合成的图片,数组
	 * @param $obmaxwidth 合成图片的宽
	 * @param $obmaxheight 合成图片的高
	 * @return unknown_type true/false
	 * @author alfa@YOKA 2009-10-14
	 */
	function ImagickCompositeFormatSize($scimgArr,$obwidth,$obheight)//,&$obtop, &$obmaxwidth,&$obmaxheight)
	{
		/// 按order排序
		usort($scimgArr,array("Images","sortByOrder"));

		/// 计算合成图片的大小
		$minleft = 10000;
		$maxwidth = 0;
		$mintop = 10000;
		$maxheight = 0;
		foreach ($scimgArr as $scimg)
		{
            $rect = $scimg['rect'];
            /// 兼容老版本(没有rect属性)
            if(is_array($rect)){
                /// 使用 rect 来计算，这样 旋转也正确了。
            	$minleft = min($minleft, $rect['x1']);
	            $mintop = min($mintop, $rect['y1']);
	            $maxwidth = max($maxwidth, $rect['x2']);
	            $maxheight = max($maxheight, $rect['y2']);
            }else{
                $minleft = min($minleft,$scimg['x']);
	            $mintop = min($mintop,$scimg['y']);
                $maxwidth = max($maxwidth,$scimg['x'] + $scimg['w']);
                $maxheight = max($maxheight,$scimg['y'] + $scimg['h']);
            }
		}
		$obmaxwidth = $maxwidth - $minleft;
		$obmaxheight = $maxheight - $mintop;
		$ratio = min(1, $obwidth/$obmaxwidth,$obheight/$obmaxheight);
		foreach ($scimgArr as &$scimg)
		{
            $scimg['w'] = intval($scimg['w'] * $ratio);
            $scimg['h'] = intval($scimg['h'] * $ratio);

            $rect = $scimg['rect'];
            $rect['x1'] = intval(($rect['x1'] - $minleft) * $ratio + ($obwidth - $obmaxwidth * $ratio)/2);
            $rect['y1'] = intval(($rect['y1'] - $mintop) * $ratio + ($obheight - $obmaxheight * $ratio)/2);

            if(is_array($scimg['rect']) && strpos($_SERVER[HTTP_USER_AGENT], 'MSIE') !== false)
            {
                $scimg['x'] = $rect['x1'];
                $scimg['y'] = $rect['y1'];
            }
            else
            {   /// 非IE, 偏移计算
                $rect['x2'] = intval(($rect['x2'] - $minleft) * $ratio + ($obwidth - $obmaxwidth * $ratio)/2);
                $rect['y2'] = intval(($rect['y2'] - $mintop) * $ratio + ($obheight - $obmaxheight * $ratio)/2);

                $scimg['x'] = ($rect['x2'] + $rect['x1'])/2 - $scimg['w']/2;
                $scimg['y'] = ($rect['y2'] + $rect['y1'])/2 - $scimg['h']/2;;
            }
		}
		return $scimgArr;
	}

	private function sortByOrder($a,$b)
	{
		if($a['z'] == $b['z'])
		{
			return 0;
		}
		return ($a['z'] > $b['z']) ? 1 : -1;
	}
	/**
	 * @desc 去掉背景图片
	 * @param $scimg 源图片地址(全路径)
	 * @param $obimg 目标图片(全路径)
	 * @return unknown_type
	 * @author alfa@YOKA 2009-9-16
	 */
	function ImagickClearBackground($scimg,$obimg)
	{
		$thumb = new Imagick();
		$thumb->readImage($scimg);
		$thumb->setImageMatte(true);
		/// 取左上(5,5)及右上($w-5,5)色彩的平均值做为背景色
		$bgcolor1 = $thumb->getImagePixelColor(5,5)->getColor(false);
		$bgcolor2 = $thumb->getImagePixelColor($thumb->getImageWidth()-5,5)->getColor(false);
		$bgcolor = "rgb(".intval(($bgcolor1['r']+$bgcolor2['r'])/2) .",".intval(($bgcolor1['g']+$bgcolor2['g'])/2) .",".intval(($bgcolor1['b']+$bgcolor2['b'])/2) .")";
		if(!$thumb->paintTransparentImage($bgcolor /*'#7b7b64'*//*'rgb(123,123,100)'*/,0,5000))
		{
			echo "去除背景失败";
		}
        /// 画布适应图像裁剪【去白边功能】
		$thumb->trimImage(0.0);
		$ret = array();
        $ret['w'] = $thumb->getImageWidth();
        $ret['h'] = $thumb->getImageHeight();

        $thumb->writeImage($obimg);
		$thumb->destroy();

		return $ret;
	}

	/**
	 * @desc 自定义去掉背景图片
	 * @param $scimg 源图片地址(全路径)
	 * @param $obimg 目标图片(全路径)
	 * @param $customarr 相应的点(数组)
	 * @return unknown_type
	 * @author alfa@YOKA 2009-9-16
	 */
	function ImagickClearBackgroundCustom($scimg,$obimg,$customarr)
	{
		$thumb = new Imagick();
		$thumb->readImage($scimg);

		$imagickObj = new Imagick();
		$drawObj = new ImagickDraw();

		$imagickObj ->newImage($thumb->getImageWidth(),$thumb->getImageHeight(),"white");
		$drawObj->setFillColor("red");
		$drawObj->polygon($customarr);
		$imagickObj->drawImage($drawObj);
		$imagickObj->paintTransparentImage("red",0,0);
		$thumb->compositeImage($imagickObj,imagick::COMPOSITE_ATOP,0,0);

		if(!$thumb->paintTransparentImage("white",0,0))
		{
			echo "去除背景失败";
		}

		/// 如果字节少于五个，则表明传递的是输出格式信息
		if(strlen($obimg) < 5)
		{
			/// 如果没有指定，就按原格式输出
			if(empty($obimg))
			{
				$obimg = $thumb->getImageFormat();
			}
			header("Content-Type: image/{$obimg}");
			echo $thumb->getImageBlob();
		}
		else
		{
			$ret = $thumb->writeImage($obimg);
		}

		$thumb->destroy();

	}

	/**
	 * @desc 给一个图片加上背景色
	 * @param $scimg 源图片
	 * @param $obimg 目标图片
	 * @param $bgcolor 背景颜色
	 * @return bool
	 * @author alfa@YOKA 2010-1-18
	 */
	public static function ImagickAddBackground($scimg,$obimg="",$bgcolor="white")
	{
		$srcImgObj = new Imagick();
		$objImgObj = new Imagick();
		$srcImgObj -> readImage($scimg);
		$objImgObj->newImage($srcImgObj->getImageWidth(), $srcImgObj->getImageHeight(), $bgcolor);
		$objImgObj->setImageOpacity(1);
		$objImgObj->compositeImage($srcImgObj,$srcImgObj->getImageCompose(),0,0);

		if(empty($obimg))
		{
			$obimg = $scimg;
		}
		$objImgObj->writeImage($obimg);

		/// 注销
		$srcImgObj->destroy();
		$objImgObj->destroy();
		return true;

	}

	/**
	 * @desc 得到图片所包含的颜色
	 * 分析所有颜色花费的时间较长（在我的机器上640X480的图片需要花费30S），所以先对图片进行
	 * 了缩放，更改成统一大小100x100并保持源比率，然后再对颜色进行检测
	 * 1、保持源比率缩放图片为100x100，
	 * 2、扫描图片，得到图片相素列表及相素个数
	 * 3、对图片相素列表进行标准相素列表匹配，原则是计算最短距离(r-r[0..n])^2+(g-g[0-n])^2+(b-b[0-n])^2
	 * 4、对归类进行排序，返回
	 * @param $scimg 图片源路径
	 * @param $colorlist 颜色列表
	 * @param $limit 返回颜色列表个数
	 * @return $retcolorArr 对应的所有颜色
	 * @author alfa@YOKA 2009-10-14
	 */
	static function ImagickGetColorsList($scimg,$colorlist,$limit = 5)
	{

		$imagickObj = new Imagick();
		/// 读取图片文件并进行缩放
		$imagickObj->readImage($scimg);
		if($imagickObj->getImageWidth() > 100 && $imagickObj->getImageHeight() > 100 )
		{
			$imagickObj->resizeImage(100,100,Imagick::FILTER_UNDEFINED,1,true);
		}
		/// 得到缩放后的宽高
		$height = $imagickObj->getImageHeight();
		$width = $imagickObj->getImageWidth();
		/// 扫描图片相素
		$imgcolorArr = array();

		for ($w = 0; $w < $width; $w++)
		{
			for ($h = 0; $h < $height; $h++)
			{

				$pixelObj = $imagickObj->getImagePixelColor($w,$h);
				$pixelRGBValue =  $pixelObj->getColorAsString();
				if(isset($imgcolorArr[$pixelRGBValue]))
				{
					$imgcolorArr[$pixelRGBValue]['num'] ++;
				}
				else
				{
					$pixelRGB = $pixelObj->getColor(false);
					$pixelRGB['num'] = 1;
					$imgcolorArr[$pixelRGBValue] = $pixelRGB;
				}

			}
		}

		/// 匹配相素并归类
		$allcolorArr = array();
		$imgcolorblueArr = array();
		$imgcolorbluenum = 0;
		foreach ($imgcolorArr as $imgcolor)
		{
			/// 查到最近标准相素
			$colordistinct = 256*256*3;
			$closecolor = array();
			foreach ($colorlist as $colorlabel)
			{
				$tmpdistinct = pow($imgcolor["r"] - $colorlabel["colorR"],2) + pow($imgcolor["g"] - $colorlabel["colorG"],2) + pow($imgcolor["b"] - $colorlabel["colorB"],2);
				if($tmpdistinct * 4 <= $colorlabel['mindis'])
				{
					$closecolor = $colorlabel;
					$colordistinct = $tmpdistinct;
					break;
				}
				if($colordistinct > $tmpdistinct)
				{
					$closecolor = $colorlabel;
					$colordistinct = $tmpdistinct;
				}
			}


			/// 合并
			if(isset($allcolorArr[$closecolor["label"]]))
			{
				$closecolor["num"] = $imgcolor['num'] + $allcolorArr[$closecolor["label"]]["num"];
			}
			$allcolorArr[$closecolor["label"]] = $closecolor;
		}
		/// 排序，并提取前 5 个
		usort($allcolorArr,array("Images","sortcolorbynumdesc"));
		$topfivecolor = array();
		$retnum = 0;
		foreach ($allcolorArr as $key=>$retcolor)
		{
			if($retcolor['num'] <= 1 )
			{
				break;
			}
			$topfivecolor[$key] = $retcolor;
			$retnum ++;
			if($retnum >= $limit)
			{
				break;
			}
		}
		return $topfivecolor;
	}

	/**
	 * @desc 排序
	 * @param $a
	 * @param $b
	 * @return unknown_type
	 * @author alfa@YOKA 2009-10-14
	 */
	private function sortcolorbynumdesc($a,$b)
	{
		if($a['num'] == $b['num']) return 0;
		return $a['num'] < $b['num'] ? 1 : -1;
	}

}
?>
