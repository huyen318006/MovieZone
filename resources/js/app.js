import './bootstrap';

import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

import Swiper from 'swiper';
import { Navigation, Pagination, Autoplay } from 'swiper/modules';

window.Swiper = Swiper;
window.Navigation = Navigation;
window.Pagination = Pagination;
window.Autoplay = Autoplay;
// chạy banner và tin tức

document.addEventListener('DOMContentLoaded', ()=>{
   const heroSlides =
document.querySelectorAll('.hero-slide');

if(heroSlides.length){

    let heroIndex=0;

    setInterval(()=>{

        heroSlides.forEach(slide=>{
            slide.classList.remove('active');
        });

        heroIndex=
        (heroIndex+1)%heroSlides.length;

        heroSlides[heroIndex]
        .classList.add('active');

    },5000);
}

    const newsSlides=document.querySelectorAll('.news-slide');
    const newsDots=document.querySelectorAll('.news-dots span');
    if(newsSlides.length){
        let newsIndex=0;
        setInterval(()=>{
            newsSlides.forEach(s=>s.classList.remove('active'));
            newsDots.forEach(d=>d.classList.remove('active'));
            newsIndex=(newsIndex+1)%newsSlides.length;
            newsSlides[newsIndex].classList.add('active');
            newsDots[newsIndex].classList.add('active');
        },5000);
    }
});