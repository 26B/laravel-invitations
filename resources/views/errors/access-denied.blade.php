@extends('errors.layout')

@section('title', __('Access Denied'))
@section('code', '403')
@section('message', __($exception->getMessage() ?: 'Access Denied'))
