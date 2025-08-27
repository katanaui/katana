@props([
    'text' => 'Katana UI',
    'delay' => 0,
    'speed' => 30
])

<div
  x-data="decryptedText({
    text: '{{ $text ?? "Empty text" }}',
    characters: 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+-={}[]',
    speed: {{ $speed }}
  })"
  x-init="setTimeout(() => startDecrypt(), '{{ $delay }}')"
>
  <template x-for="(char, i) in displayChars" :key="i">
    <span x-text="char"></span>
  </template>
</div>

<script>
  function decryptedText({
    text,
    characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+-={}[]',
    speed = 1,
  }) {
    return {
      text,
      characters,
      speed,

      displayChars: [],
      revealedIndices: [],
      scrambleInterval: null,

      startDecrypt() {
        const delayBetween = this.getDelayBetween(this.speed);
        const processedText = String(this.text);

        this.displayChars = processedText.split('').map((char) => {
          if (char === ' ' || !this.characters.includes(char)) return char;
          return this.getRandomChar();
        });

        let revealIndex = 0;

        this.scrambleInterval = setInterval(() => {
          this.displayChars = processedText.split('').map((char, i) => {
            if (this.revealedIndices.includes(i) || char === ' ' || !this.characters.includes(char)) {
              return char;
            }
            return this.getRandomChar();
          });
        }, 50);

        const revealNext = () => {
          if (revealIndex >= processedText.length) {
            clearInterval(this.scrambleInterval);
            setTimeout(() => {
              this.displayChars = processedText.split('');
              this.revealedIndices = [...Array(processedText.length).keys()];
            }, 100);
            return;
          }

          while (revealIndex < processedText.length &&
                 (processedText[revealIndex] === ' ' || !this.characters.includes(processedText[revealIndex]))) {
            this.revealedIndices.push(revealIndex);
            revealIndex++;
          }

          if (revealIndex < processedText.length) {
            this.revealedIndices.push(revealIndex);
            revealIndex++;
          }

          setTimeout(revealNext, delayBetween);
        };

        revealNext();
      },

      getDelayBetween(speed, minDelay = 5, maxDelay = 300) {
            const normalized = (speed - 1) / 99; // maps 1–100 to 0–1
            const inverse = 1 - normalized;
            return Math.round(minDelay + Math.pow(inverse, 3.5) * (maxDelay - minDelay));
        },

      getRandomChar() {
        return this.characters[Math.floor(Math.random() * this.characters.length)];
      },
    };
  }
</script>