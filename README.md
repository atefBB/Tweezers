# Tweezers

[![Build Status](https://travis-ci.org/Imangazaliev/Tweezers.svg)](https://travis-ci.org/Imangazaliev/Tweezers)
[![Total Downloads](https://poser.pugx.org/imangazaliev/tweezers/downloads)](https://packagist.org/packages/imangazaliev/tweezers)
[![Latest Stable Version](https://poser.pugx.org/imangazaliev/tweezers/v/stable)](https://packagist.org/packages/imangazaliev/tweezers)
[![License](https://poser.pugx.org/imangazaliev/tweezers/license)](https://packagist.org/packages/imangazaliev/tweezers)

## Содержание

- [Установка](#Установка)
- [Быстрый старт](#Быстрый-старт)

## Установка

Для установки Tweezers выполните команду:

    composer require imangazaliev/tweezers

## Быстрый старт

```php    
use Tweezers\Crawler;

$crawler = new Crawler('http://www.example.com/');

$form = $crawler->form('.foo');

$data = [
    'foo' => 'foobar',
    'bar' => 'foobar'
];

$formData = $form->fill($data)->getValues();
```