@extends('errors.layout')

@section('title', __('Invalid code'))
@section('code', '422')
@section('message', __($exception->getMessage() ?: 'Invalid code'))
