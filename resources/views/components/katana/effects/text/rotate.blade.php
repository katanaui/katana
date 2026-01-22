@props([
    'text' => 'THIS IS THE KATANAUI TEXT EFFECT COMPONENT! —',
    'fontSize' => '10',
    'fontWeight' => 'normal',
    'speed' => '25s',
    'reverse' => false,
])

<style>
.animate-spin {
  animation: spin {{ $speed }} linear infinite {{ $reverse ? 'reverse' : 'normal' }};
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
<div {{ $attributes->twMerge('animate-spin size-40 text-black') }}>
<svg
  viewBox="0 0 100 100"
  xmlns="http://www.w3.org/2000/svg"
>
  <path
    id="circlePath"
    d="
      M 10, 50
      a 40,40 0 1,1 80,0
      40,40 0 1,1 -80,0
    "
    class="hidden"
  />
  <text>
    <textPath href="#circlePath" font-size="{{ $fontSize }}" font-weight="{{ $fontWeight }}" class="fill-current">
        {{ $text }}
    </textPath>
  </text>
</svg>
</div>