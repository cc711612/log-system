<?
namespace App\Helpers\Enums;


enum StatusEnum: string
{
    case INITIAL = 'initial';
    case QUEUE = 'queue';
    case PROCESSING = 'in progress';
    case FAILURE = 'failure';
    case SUCCESS = 'success';
}