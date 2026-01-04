@props([
    'items' => [],
    'height' => 'h-[280px] sm:h-[380px] lg:h-[500px] max-w-[1400px]:h-[700px]',
    'autoPlay' => true,
    'autoPlayTimeout' => 4000,
    'gap' => 'md:gap-5',
    'showControls' => true,
    'showProgress' => true,
])

<div class="w-full h-full rounded-xl overflow-hidden">
    @php
        // If items is empty, try to get from slot content or default structure
        if (empty($items) && isset($block)) {
            $items = [];
            foreach ($block->children as $child_block){
                array_push($items, [
                    'title' => $child_block->content['title'] ?? '',
                    'image' => $child_block->image ?? $child_block->getFirstMediaUrl() ?? ''
                ]);
            }
        }
    @endphp

    @if(empty($items))
        <div class="flex justify-center max-w-6xl p-10 mx-auto mb-10 bg-gray-100 rounded-md items-center">
            <p class="text-gray-500">No Items Defined</p>
        </div>
    @else

        <div x-data="{
            items: JSON.parse('{{ json_encode($items) }}'),
            activeIndex: 0,
            autoPlay: {{ $autoPlay ? 'true' : 'false' }},
            autoPlayInterval: null,
            autoPlayTimeout: {{ $autoPlayTimeout }},
            autoPlayTimeRemaining: 0,
            isPaused: true,
            
            percent: 0,
            percentInterval: null,
            resetItemProgress() {
                let itemProgresses = document.querySelectorAll('.item-progress');
                for(let i=0; i<itemProgresses.length; i++) {
                    itemProgresses[i].dispatchEvent(new CustomEvent('reset'));
                }
            },
            pause(){
                clearInterval(this.percentInterval);
            },
            checkForReset(index){
                if(this.activeIndex != index){
                    this.percent = 0;
                }
            },
            next(){
                this.percent = 0;
                if(this.activeIndex+1 > this.items.length-1){
                    this.activeIndex = 0;
                } else {
                    this.activeIndex += 1;
                }
            },
            previous() {
                this.percent = 0;
                if(this.activeIndex-1 <= 0){
                    this.activeIndex = this.items.length-1;
                } else {
                    this.activeIndex -= 1;
                }
            },
            play(){
                let that = this;
                clearInterval(this.percentInterval);

                this.percentInterval = setInterval(function(){ 
                    that.percent += 1 
                    currentPercent = that.percent;
                }, 40);
            }
        }" 
        x-init="
            setTimeout(function(){
                if(document.getElementById('progress-' + activeIndex)) {
                    document.getElementById('progress-' + activeIndex).dispatchEvent(new CustomEvent('play'));
                }
                if(autoPlay) play();
            }, 1);

            $watch('percent', (value) => {
                if(value >= 100){
                    next();
                    if(autoPlay) play();
                }
            });
        "
        @start-slider.window="if(isPaused){ console.log('playing...'); play(); activeIndex=0; if(document.getElementById('progress-' + activeIndex)) { document.getElementById('progress-' + activeIndex).dispatchEvent(new CustomEvent('play')); } isPaused = false; }"
        @mouseenter="pause()" 
        @mouseleave="if(autoPlay) play()" 
        class="flex items-center {{ $gap }} {{ $height }} w-full" 
        :key="activeIndex">
            
            <template x-for="(item, index) in items" :key="index">
                
                <div 
                    :class="{ 
                        'w-0 md:w-2/12' : activeIndex != index, 
                        'w-full md:w-1/2' : activeIndex == index, 
                        'md:rounded-r' : index == 0, 
                        'md:rounded' : index != 0 && index != (items.length-1), 
                        'md:rounded-l' : index == (items.length-1) 
                    }"
                    @mouseenter="checkForReset(index); activeIndex = index;"
                    class="{{ $height }} bg-gray-100 relative ease-out duration-300 grid-flow-col overflow-hidden">
                    
                    <img :src="item.image" class="object-cover w-full h-full" :alt="item.title + ' image'" />
                    <div x-show="activeIndex != index" class="absolute inset-0 w-full h-full bg-black/50"></div>
                
                    @if($showControls || $showProgress)
                        <div x-show="activeIndex == index" class="absolute top-0 right-0 flex items-center w-auto mt-3 mr-3 overflow-hidden text-white bg-black border rounded shadow-xl backdrop-blur-sm border-white/30 h-9 bg-opacity-60">
                            
                            @if($showControls)
                                <button @click="previous()" class="relative h-full px-1.5 border-r md:hidden block border-gray-400/30 text-white/60 hover:text-white/90 hover:bg-white/10">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                        <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            @endif
                            
                            <div class="flex items-center px-1 space-x-1">
                                @if($showProgress)
                                    <div :id="'progress-' + index" 
                                        class="w-3 h-3 translate-x-1.5 rounded-full item-progress opacity-80 transition-all duration-100 ease-linear" 
                                        :style="'background:conic-gradient(rgba(255, 255, 255, 0.4) ' + percent + '%, rgba(255, 255, 255, 1) 0);'">
                                    </div>
                                @endif
                                <p x-text="item.title" class="flex items-center h-full px-2.5 text-xs font-medium"></p>
                            </div>
                            
                            @if($showControls)
                                <button @click="next()" class="relative h-full px-1.5 md:hidden block border-l border-gray-400/30 text-white/60 hover:text-white/90 hover:bg-white/10">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    @endif

                </div>
            </template>
            
        </div>
    @endif
</div>