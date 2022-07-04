@extends('errors.layout')

@section('title', __('Expired'))
@section('code', '401')
@section('message', __($exception->getMessage() ?: 'Expired'))
