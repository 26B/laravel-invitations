<?php

namespace TwentySixB\LaravelInvitations\View\Components;


use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use chillerlan\QRCode\QRCode;

class Code extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
		protected string $route
	)
    {
		//
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
		$qrcode = (new QRCode)->render($this->route);

        return view('invitations::components.code')
			->with('qrcode', $qrcode);
    }
}

