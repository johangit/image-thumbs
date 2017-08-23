<?php

namespace JohanCode\ImageThumbs;

use File as LaravelFile;
use Image as InterventionImage;
use Illuminate\Support\Facades\Storage;

trait ImageThumbsTrait
{
    private function saveImageThumbsField($fieldName, $files, $multiple = false)
    {
        $diskName = config('image-thumbs.disk_name');

        if (!$this->id || $this->fieldIsModified($fieldName, $files, $diskName)) {
            $newValue = $files ? $this->genereateThumbs($fieldName, $diskName, $files, $multiple) : null;

            if ($this->getOriginal($fieldName)) {
                $oldValue = json_decode($this->getOriginal($fieldName), true);

                if ($oldValue) {
                    $this->removeOldValue($oldValue, $fieldName, $diskName);
                }
            }
        } else {
            $newValue = json_decode($this->getOriginal($fieldName), true);
        }

        return $newValue;
    }


    public function removeImages($fieldName)
    {
        $diskName = config('image-thumbs.disk_name');

        $imagesList = json_decode($this->getOriginal($fieldName), true);
        if ($imagesList) {
            $this->removeOldValue($imagesList, $fieldName, $diskName);
        }
    }

    private function removeOldValue(array $oldValue, $fieldName, $diskName)
    {
        $filesList = array_flatten($oldValue);
        $filesList = array_map(function ($item) use ($diskName) {
            return public_path(Storage::disk($diskName)->url($item));
        }, $filesList);

        LaravelFile::delete($filesList);
    }


    private function genereateThumbs($fieldName, $diskName, $files, $multiple = false)
    {
        $newValue = [];

        if ($multiple) {
            foreach ($files as $file) {
                $newValue[] = $this->genereateImageThumbs($fieldName, $diskName, $file);
            }
        } else {
            if (is_array($files)) {
                $files = $files[0];
            }

            $newValue = $this->genereateImageThumbs($fieldName, $diskName, $files);
        }

        return $newValue;
    }


    private function genereateImageThumbs($fieldName, $diskName, $file)
    {
        $newImageThumbsPathList = [];


        if (is_string($file)) {
            $fileExt = substr($file, strrpos($file, ".") + 1);
            $newFileName = md5(uniqid()) . "." . $fileExt;
        } else if (get_class($file) === "Illuminate\Http\UploadedFile") {
            $newFileName = md5(uniqid()) . "." . $file->getClientOriginalExtension();
        } else {
            throw new Exception("error type file");
        }

        $baseFolder = "system/" . strtolower(class_basename($this)) . "/" . $fieldName;
        $postfixFolder = substr($newFileName, 0, 2);
        $originalFilePath = $baseFolder . "/original/" . $postfixFolder;


        if (is_string($file)) {
            // if file path start with cur domain + storage + tempimage - its temp file. just copy it.
            $tempFilePathPart = url(Storage::disk($diskName)->url("")) . "/tempimage/";
            if (strpos($file, $tempFilePathPart) === 0) {
                $file = public_path(str_replace(url(""), "", $file));
            }

            // if file path start with cur domain + storage - file on server. just copy it.
            $tempFilePathPart = url(Storage::disk($diskName)->url("")) . "/";

            if (strpos($file, $tempFilePathPart) === 0) {
                $file = public_path(str_replace(url(""), "", $file));
            }

            $newImageThumbsPathList['original'] = Storage::disk($diskName)->putFileAs($originalFilePath, new \Illuminate\Http\File($file), $newFileName);
        } else if (get_class($file) === "Illuminate\Http\UploadedFile") {
            $newImageThumbsPathList['original'] = Storage::disk($diskName)->putFileAs($originalFilePath, $file, $newFileName);
        }


        if (method_exists($this, "getImageThumbsParams")) {
            foreach ($this->getImageThumbsParams($fieldName) as $typeName => $manipulations) {
                $filePath = $baseFolder . "/" . $typeName . "/" . $postfixFolder;

                Storage::disk($diskName)->makeDirectory($filePath);

                $img = InterventionImage::make(public_path(Storage::disk($diskName)->url($newImageThumbsPathList['original'])));
                foreach ($manipulations as $method => $arguments) {
                    call_user_func_array([$img, $method], $arguments);
                }

                $newFileFullPath = public_path(Storage::disk($diskName)->url("") . $filePath . "/" . $newFileName);
                $img->save($newFileFullPath);

                $newImageThumbsPathList[$typeName] = $filePath . "/" . $newFileName;
            }
        }


        return $newImageThumbsPathList;
    }


    private function getImageValue($fieldName)
    {
        $diskName = config('image-thumbs.disk_name');

        $value = $this->attributes[$fieldName];
        $rawValue = json_decode($value, true);

        if ($rawValue && is_array($rawValue)) {
            $convertUri = function ($valuesList) use ($diskName) {
                $result = [];
                foreach ($valuesList as $type => $uri) {
                    $result[$type] = env("APP_URL") . Storage::disk($diskName)->url($uri);
                }

                return $result;
            };

            if (isset($rawValue['original'])) {
                $value = (object)$convertUri($rawValue);
            } else {
                $value = [];
                foreach ($rawValue as $item) {
                    $value[] = (object)$convertUri($item);
                }
            }
        } else {
            $value = null;
        }

        return $value;
    }


    private function fieldIsModified($fieldName, $newValue, $diskName)
    {
        $currentValue = json_decode($this->getOriginal($fieldName), true);
        $domainPrefix = env("APP_URL") . Storage::disk($diskName)->url("");

        if (isset($currentValue['original'])) {
            $currentPathList = [$currentValue['original']];
        } else {
            $currentPathList = collect($currentValue)->pluck('original')->toArray();
        }

        $newPathList = collect($newValue)
            ->map(function ($item, $key) use ($domainPrefix) {
                return str_replace($domainPrefix, "", $item);
            })
            ->toArray();


        return ($currentPathList !== $newPathList);
    }
}