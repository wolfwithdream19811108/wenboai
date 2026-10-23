<?php

use think\Route;



Route::post('ai/chat/v1/chat/completions', 'ai/Chat/v1ChatCompletions');
Route::get('ai/chat/v1/models', 'ai/Chat/v1Models');