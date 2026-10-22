<?php
require __DIR__.'/../includes/config.php';require __DIR__.'/../includes/functions.php';$u=require_role(['owner']);verify_csrf();$cats=category_rows($pdo);$error='';$selected=(int)($_GET['category']??$_POST['category_id']??0);
$st=$pdo->prepare('SELECT * FROM premises WHERE owner_id=? ORDER BY id DESC LIMIT 1');$st->execute([$u['id']]);$premise=$st->fetch();
if($_SERVER['REQUEST_METHOD']==='POST'){
  $categoryId=(int)($_POST['category_id']??0);$title=trim($_POST['title']??'');$description=trim($_POST['description']??'');$location=trim($_POST['location']??'');
  $catCheck=$pdo->prepare('SELECT * FROM categories WHERE id=?');$catCheck->execute([$categoryId]);$cat=$catCheck->fetch();
  if(!$cat||!$title||!$description||!$location)$error='Please complete the issue type, title, description and location.';
  else {
    try{$photo=upload_attachment_data((string)($_POST['photo_data']??''));$pdo->beginTransaction();$requestNo='SDC2026-'.str_pad((string)random_int(1,99999),5,'0',STR_PAD_LEFT);$st=$pdo->prepare('INSERT INTO requests(request_no,owner_id,premise_id,category_id,title,description,location,township,status,priority) VALUES(?,?,?,?,?,?,?,?,0,?)');$st->execute([$requestNo,$u['id'],$premise['id']??null,$categoryId,$title,$description,$location,$premise['township']??'Elmina',($_POST['priority']??'normal')==='urgent'?'urgent':'normal']);$rid=(int)$pdo->lastInsertId();if($photo){$st=$pdo->prepare('INSERT INTO attachments(request_id,uploaded_by,original_name,stored_name,mime_type,size_bytes) VALUES(?,?,?,?,?,?)');$st->execute([$rid,$u['id'],mb_substr(basename(str_replace(chr(0),'',(string)($_POST['photo_name']??'photo'))),0,200),$photo['name'],$photo['mime'],$photo['size']]);}log_status($pdo,$rid,0,$u['id'],'Request submitted by premise owner.');add_notification($pdo,$u['id'],$rid,'Request received','Your request '.$requestNo.' has been received.');$pdo->commit();$_SESSION['last_request']=$rid;redirect('owner/confirmation.php?id='.$rid);}catch(Throwable $ex){if($pdo->inTransaction())$pdo->rollBack();if(isset($photo)&&$photo)@unlink(UPLOAD_DIR.'/'.$photo['name']);$error=$ex instanceof RuntimeException?$ex->getMessage():'Could not submit the request. Please try again.';}
  }
}
$pageTitle='Report an Issue';require __DIR__.'/../includes/header.php';?>
<div class="eyebrow">02 / REQUEST ASSISTANCE</div><h2>Report an <span class="red">Issue</span></h2><p class="subtext">Select an issue, describe what happened and submit it to the township coordination centre.</p><?php if($error):?><div class="flash error"><?=e($error)?></div><?php endif;?>
<form method="post" class="card" id="reportForm"><?=csrf_field()?>
<div class="field"><label for="searchCategory">Search issue type</label><input id="searchCategory" type="search" placeholder="Search fire, plumbing, security…"></div>
<div class="grid grid-3" id="categoryGrid"><?php foreach($cats as $c):?><label class="category-card category-select" data-name="<?=e(strtolower($c['name']))?>" style="cursor:pointer"><input type="radio" name="category_id" value="<?=$c['id']?>" <?=$selected===$c['id']?'checked':''?> required style="accent-color:var(--sd-red);margin:0 0 8px"><span class="category-icon <?=e(category_class($c['slug']))?>"><?=category_emoji($c['slug'])?></span><strong><?=e($c['name'])?></strong><small><?=e($c['description'])?></small></label><?php endforeach;?></div>
<div id="fireWarning" class="alert-999" style="display:none"><span style="font-size:22px">☎</span><div><strong>For fire, serious injury or immediate danger, call 999 first.</strong>Sime Darby Care coordinates supplementary support and does not replace official emergency services.</div></div>
<hr style="border:0;border-top:1px solid var(--sd-border);margin:22px 0"><div class="form-row"><div class="field"><label>Issue title *</label><input name="title" required value="<?=e($_POST['title']??'')?>" placeholder="e.g. Water leaking under kitchen sink"></div><div class="field"><label>Priority</label><select name="priority"><option value="normal">Standard assistance</option><option value="urgent" <?=($_POST['priority']??'')==='urgent'?'selected':''?>>Urgent — needs prompt attention</option></select></div></div>
<div class="field"><label>Description *</label><textarea name="description" required placeholder="Describe the issue and any important details."><?=e($_POST['description']??'')?></textarea></div>
<div class="field"><label>Location / premise address *</label><input name="location" required value="<?=e($_POST['location']??$premise['address']??'')?>" placeholder="Address, unit, street, Elmina"></div>
<div class="field"><label>Photo (optional, JPG/PNG/WEBP)</label><input type="file" id="photoInput" accept="image/jpeg,image/png,image/webp"><input type="hidden" name="photo_data" id="photoData"><input type="hidden" name="photo_name" id="photoName"><small class="muted" id="photoStatus">Photos are resized automatically before upload.</small><img id="photoPreview" class="photo-preview" alt="Selected photo preview" style="display:none;margin-top:8px"></div>
<button class="btn btn-primary btn-block">Submit Request</button></form>
<script>
const search=document.getElementById('searchCategory');search.addEventListener('input',()=>{const q=search.value.toLowerCase();document.querySelectorAll('.category-select').forEach(x=>x.style.display=x.dataset.name.includes(q)?'':'none')});
function warn(){const checked=document.querySelector('input[name="category_id"]:checked');const label=checked?.closest('.category-select');document.getElementById('fireWarning').style.display=(label?.dataset.name.includes('fire')||label?.dataset.name.includes('safety'))?'flex':'none';}
document.querySelectorAll('input[name="category_id"]').forEach(x=>x.addEventListener('change',warn));warn();
// Photo upload without multipart/form-data: resize in the browser, send as base64 in a normal POST field.
(function(){
const input=document.getElementById('photoInput'),data=document.getElementById('photoData'),nameEl=document.getElementById('photoName'),status=document.getElementById('photoStatus'),prev=document.getElementById('photoPreview'),form=document.getElementById('reportForm');
const btn=form.querySelector('button.btn-primary');let busy=false;
function reset(msg){data.value='';nameEl.value='';prev.style.display='none';status.textContent=msg||'Photos are resized automatically before upload.';}
input.addEventListener('change',()=>{
  const f=input.files[0];if(!f){reset();return;}
  if(!/^image\/(jpeg|png|webp)$/.test(f.type)){input.value='';reset('Only JPG, PNG or WEBP images are allowed.');return;}
  if(f.size>15*1024*1024){input.value='';reset('Photo is too large. Please choose one under 15 MB.');return;}
  busy=true;btn.disabled=true;status.textContent='Preparing photo…';
  const url=URL.createObjectURL(f),img=new Image();
  img.onload=()=>{
    const max=1280,sc=Math.min(1,max/Math.max(img.width,img.height)),w=Math.round(img.width*sc),h=Math.round(img.height*sc);
    const c=document.createElement('canvas');c.width=w;c.height=h;const ctx=c.getContext('2d');ctx.fillStyle='#fff';ctx.fillRect(0,0,w,h);ctx.drawImage(img,0,0,w,h);
    let q=0.82,out=c.toDataURL('image/jpeg',q);
    while(out.length>2.4*1024*1024&&q>0.4){q-=0.1;out=c.toDataURL('image/jpeg',q);}
    URL.revokeObjectURL(url);
    data.value=out;nameEl.value=f.name.replace(/\.[^.]+$/,'')+'.jpg';prev.src=out;prev.style.display='block';
    status.textContent='Photo ready ('+Math.round(out.length*0.75/1024)+' KB).';busy=false;btn.disabled=false;
  };
  img.onerror=()=>{URL.revokeObjectURL(url);input.value='';reset('Could not read that image. Please try another photo.');busy=false;btn.disabled=false;};
  img.src=url;
});
form.addEventListener('submit',e=>{if(busy)e.preventDefault();});
})();
</script><?php require __DIR__.'/../includes/footer.php';?>
