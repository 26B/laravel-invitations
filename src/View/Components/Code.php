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
		// ponytail: qrcode v5 defaults to SVG base64; v4 returns PNG base64. Both render in <img>. Pin outputType if you need a fixed format.
		$qrcode = (new QRCode)->render($this->route);

        return view('invitations::components.code')
			->with('qrcode', $qrcode);
    }
}

