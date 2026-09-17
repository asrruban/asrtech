<script setup lang="ts">
import {
    ArrowUpRight,
    Blocks,
    Check,
    Code2,
    Globe2,
    Layers3,
    Server,
    Smartphone,
} from '@lucide/vue';
</script>

<template>
    <div
        class="technical-scene"
        role="img"
        aria-label="Conceptual illustration connecting a website, mobile app, WHMCS, WordPress, and server care. Not a product screenshot."
    >
        <div class="scene-orbit orbit-one"></div>
        <div class="scene-orbit orbit-two"></div>
        <div class="scene-caption">
            <span class="scene-dot"></span> FROM IDEA TO EVERYDAY OPERATION
        </div>
        <div class="scene-browser">
            <div class="browser-toolbar">
                <div class="flex gap-1.5">
                    <span></span><span></span><span></span>
                </div>
                <div class="browser-address">
                    <Globe2 class="size-3" /> your next project
                </div>
                <ArrowUpRight class="size-3.5" />
            </div>
            <div class="browser-body">
                <div class="flex items-center justify-between">
                    <div class="mini-brand">
                        <Layers3 class="size-4" /> Your business
                    </div>
                    <div class="flex gap-2"><i></i><i></i></div>
                </div>
                <div class="mini-headline">
                    An idea.<br />A better experience.
                </div>
                <div class="mini-copy"></div>
                <div class="mini-copy short"></div>
                <div class="mini-cta">
                    Built around you <ArrowUpRight class="size-3" />
                </div>
                <div class="mini-modules">
                    <div>
                        <Code2 class="size-5" /><span>Thoughtful code</span>
                    </div>
                    <div>
                        <Blocks class="size-5" /><span>Connected systems</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="scene-code">
            <div
                class="flex items-center gap-2 border-b border-white/10 pb-3 text-[10px] text-teal-200"
            >
                <Code2 class="size-3.5" /> a practical approach
            </div>
            <div class="code-line">
                <span>01</span><b>understand</b>(your_needs)
            </div>
            <div class="code-line">
                <span>02</span><b>build</b>(the_right_solution)
            </div>
            <div class="code-line">
                <span>03</span><b>support</b>(what_comes_next)
            </div>
            <div class="mt-4 flex items-center gap-2 text-[10px] text-teal-200">
                <Check class="size-3" /> Made to work together
            </div>
        </div>
        <div class="scene-mobile">
            <div class="phone-speaker"></div>
            <div class="phone-app-icon"><Smartphone class="size-5" /></div>
            <div class="phone-title">On the move.</div>
            <div class="phone-line"></div>
            <div class="phone-line short"></div>
            <div class="phone-tile">
                <span></span><span></span><span></span>
            </div>
        </div>
        <div class="scene-system">
            <Server class="size-4 text-teal-700" />
            <div>
                <strong>Build. Connect. Maintain.</strong
                ><span>Web · Mobile · WHMCS · WordPress</span>
            </div>
        </div>
        <span class="scene-index">ASR / ENGINEERING WITH PURPOSE</span>
    </div>
</template>

<style scoped>
.technical-scene {
    position: relative;
    min-height: 490px;
    isolation: isolate;
    border-radius: 50% 45% 40% 45%;
    background: radial-gradient(ellipse at 58% 45%, #dfede5, transparent 69%);
    color: #172c2c;
}
.scene-caption {
    position: absolute;
    top: 14px;
    left: 15%;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #61756c;
    font-size: 8px;
    font-weight: 600;
    letter-spacing: 0.17em;
}
.scene-dot {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: #087f75;
}
.scene-orbit {
    position: absolute;
    z-index: -1;
    border: 1px solid #ceded5;
    border-radius: 50%;
    transform: rotate(-24deg);
}
.orbit-one {
    inset: 8% -3% 2% 4%;
}
.orbit-two {
    inset: 20% 7% 14% -5%;
    transform: rotate(24deg);
}
.scene-browser {
    position: absolute;
    top: 65px;
    left: 7%;
    width: 75%;
    overflow: hidden;
    border: 1px solid #cfddd6;
    border-radius: 10px;
    background: #fff;
    box-shadow: 0 20px 40px #163f2510;
    transform: rotate(-3deg);
}
.browser-toolbar {
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-inline: 12px;
    border-bottom: 1px solid #e8eeea;
    background: #f7faf8;
    color: #80948b;
}
.browser-toolbar .flex span {
    width: 5px;
    height: 5px;
    border: 1px solid #b5c5bc;
    border-radius: 50%;
}
.browser-address {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    width: 55%;
    border: 1px solid #e3ebe5;
    border-radius: 4px;
    font-size: 8px;
}
.browser-body {
    padding: 24px;
}
.mini-brand {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 10px;
    font-weight: 600;
}
.browser-body i {
    width: 20px;
    height: 3px;
    background: #d8e4dc;
    border-radius: 3px;
}
.mini-headline {
    margin-top: 27px;
    font-size: 26px;
    font-weight: 600;
    line-height: 1.12;
    letter-spacing: -1px;
}
.mini-copy {
    width: 68%;
    height: 4px;
    border-radius: 3px;
    background: #e3eae6;
    margin-top: 13px;
}
.mini-copy.short {
    width: 48%;
    margin-top: 6px;
}
.mini-cta {
    display: inline-flex;
    align-items: center;
    gap: 15px;
    margin-top: 15px;
    padding: 7px 10px;
    background: #087f75;
    color: #fff;
    border-radius: 4px;
    font-size: 8px;
}
.mini-modules {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 9px;
    margin-top: 24px;
}
.mini-modules > div {
    display: flex;
    flex-direction: column;
    gap: 12px;
    border: 1px solid #dce8e0;
    border-radius: 5px;
    padding: 13px;
    background: #f4f8f5;
    color: #457766;
    font-size: 8px;
}
.scene-code {
    position: absolute;
    bottom: 30px;
    left: 0;
    width: 64%;
    padding: 18px;
    border: 1px solid #385c51;
    border-radius: 8px;
    background: #183c36;
    box-shadow: 0 15px 35px #0c403529;
    transform: rotate(2deg);
    font-family: ui-monospace, monospace;
    color: #d9e9e1;
}
.code-line {
    display: flex;
    gap: 5px;
    font-size: 9px;
    white-space: nowrap;
    margin-top: 13px;
}
.code-line > span {
    color: #81a092;
    margin-right: 10px;
}
.code-line > b {
    color: #9cd5ae;
    font-weight: normal;
}
.scene-mobile {
    position: absolute;
    right: 1%;
    top: 149px;
    width: 24%;
    height: 221px;
    padding: 12px;
    border: 4px solid #234c40;
    border-radius: 21px;
    background: #f4f7e9;
    box-shadow: 0 10px 30px #1743371c;
    transform: rotate(8deg);
}
.phone-speaker {
    width: 30%;
    height: 4px;
    background: #234c40;
    margin: 0 auto 20px;
    border-radius: 6px;
}
.phone-app-icon {
    display: grid;
    place-items: center;
    width: 34px;
    height: 34px;
    border: 1px solid #bfcfb8;
    border-radius: 9px;
    color: #487447;
}
.phone-title {
    margin-top: 12px;
    font-size: 10px;
    font-weight: 700;
}
.phone-line {
    height: 3px;
    width: 100%;
    background: #ccd8c3;
    margin-top: 8px;
}
.phone-line.short {
    width: 60%;
    margin-top: 4px;
}
.phone-tile {
    display: flex;
    gap: 5px;
    align-items: end;
    height: 48px;
    margin-top: 13px;
    padding: 8px;
    border-radius: 4px;
    background: #e0e9d5;
}
.phone-tile > span {
    height: 65%;
    width: 25%;
    background: #799f76;
    border-radius: 2px;
}
.phone-tile > span:nth-child(2) {
    height: 100%;
}
.phone-tile > span:nth-child(3) {
    height: 80%;
}
.scene-system {
    position: absolute;
    bottom: 62px;
    right: -2%;
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 12px;
    border: 1px solid #d3dfd7;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 9px 22px #173a2010;
    transform: rotate(-3deg);
}
.scene-system strong {
    display: block;
    font-size: 9px;
    font-weight: 600;
}
.scene-system span {
    display: block;
    margin-top: 3px;
    font-size: 7px;
    color: #788a80;
}
.scene-index {
    position: absolute;
    bottom: 0;
    right: 5%;
    font-size: 7px;
    letter-spacing: 0.15em;
    color: #7a8b81;
}
@media (max-width: 639px) {
    .technical-scene {
        min-height: 430px;
        margin-inline: 0;
    }
    .scene-browser {
        top: 55px;
        width: 81%;
        left: 2%;
    }
    .browser-body {
        padding: 17px;
    }
    .mini-headline {
        font-size: 24px;
    }
    .scene-code {
        width: 74%;
        bottom: 25px;
        padding: 12px;
    }
    .code-line {
        font-size: 8px;
        gap: 3px;
    }
    .code-line > span {
        margin-right: 4px;
    }
    .scene-mobile {
        height: 198px;
        width: 25%;
        top: 127px;
        right: 0;
        padding: 9px;
    }
    .scene-system {
        bottom: 59px;
        right: -1%;
        padding: 9px;
    }
    .scene-caption {
        font-size: 7px;
        left: 7%;
    }
    .scene-system strong {
        font-size: 8px;
    }
    .scene-system span {
        font-size: 6px;
    }
}
</style>
