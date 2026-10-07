import fs from 'node:fs/promises';
import sharp from 'sharp';
const selections={
 'front-washer':{file:'17158638.jpg',rect:[.08,.25,.59,1],id:17158638},
 'top-washer':{file:'31602334.jpg',rect:[.30,.47,.65,1],id:31602334},
 dryer:{file:'7614539.jpg',rect:[.70,.20,.92,.67],id:7614539},
 microwave:{file:'8135120.jpg',rect:[.018,.33,.27,.565],id:8135120},
 kettle:{file:'30319669.jpg',rect:[0,0,1,1],id:30319669},
 cooker:{file:'cooker-pixabay.jpg',rect:[.08,.66,.78,1],source:'https://pixabay.com/photos/stove-oven-range-back-splash-4994398/',license:'https://pixabay.com/service/license-summary/'},
 vacuum:{file:'3616735.jpg',rect:[.32,.13,.99,.88],id:3616735},
 'pressure-washer':{file:'4876669.jpg',rect:[.10,.30,.95,.97],id:4876669},
 tv:{file:'6297091.jpg',rect:[.25,.30,.75,.66],id:6297091},
 fridge:{file:'6631793.jpg',rect:[.02,.34,.31,.99],id:6631793},
 freezer:{file:'freezer-cc0.jpg',rect:[0,.26,1,.84],source:'https://commons.wikimedia.org/wiki/File:Freezer_1.jpg',license:'https://creativecommons.org/publicdomain/zero/1.0/',author:'Laura Stoinski'},
 ac:{file:'38788452.jpg',rect:[0,0,1,1],id:38788452},
 steam:{file:'steam-unsplash.jpg',rect:[.30,.22,.94,1],source:'https://unsplash.com/photos/steam-cleaner-sanitizing-tiled-wall-in-bathroom-LB3y1u7h61c',license:'https://unsplash.com/license',author:'Aurum Gebäudereinigung Kassel'}
};
await fs.mkdir('tmp/product-photo-prepared',{recursive:true});
const previews=[];
for(const [category,choice] of Object.entries(selections)){
 const path='tmp/product-photo-originals/'+choice.file;const meta=await sharp(path).metadata();
 const [x,y,x2,y2]=choice.rect;const left=Math.round(x*meta.width),top=Math.round(y*meta.height);const width=Math.min(meta.width-left,Math.round((x2-x)*meta.width)),height=Math.min(meta.height-top,Math.round((y2-y)*meta.height));
 const focused=await sharp(path).extract({left,top,width,height}).toBuffer();
 choice.source??='https://www.pexels.com/photo/'+choice.id+'/';choice.license??='https://www.pexels.com/license/';choice.assets=[];
 for(let view=0;view<3;view++){
  let image=sharp(focused);
  if(view>0){const w=Math.round(width*.76),h=Math.round(height*.76);image=image.extract({left:Math.round((width-w)*(view===1?.10:.90)),top:Math.round((height-h)*(view===1?.10:.90)),width:w,height:h});}
  const name=category+'-'+['main','detail-1','detail-2'][view]+'.jpg';
  await image.resize(1000,1000,{fit:'contain',background:'#ffffff'}).jpeg({quality:90}).toFile('tmp/product-photo-prepared/'+name);choice.assets.push(name);
  previews.push({input:await sharp('tmp/product-photo-prepared/'+name).resize(230,230).toBuffer(),left:view*250,top:Object.keys(selections).indexOf(category)*255});
 }
}
await sharp({create:{width:750,height:13*255,channels:3,background:'#eef2f7'}}).composite(previews).jpeg({quality:90}).toFile('tmp/product-photo-selected-preview.jpg');
await fs.writeFile('tmp/product-photo-selections.json',JSON.stringify(selections,null,2));
console.log('Prepared 13 matching category sets, each with main image and two detail crops; no downloads.');
