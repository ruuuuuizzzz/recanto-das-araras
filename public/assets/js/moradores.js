let blocoAtivo = null, paginaMoradores = 1, requestVersion = 0, searchTimer;
const lista = document.getElementById('lista');
const pagination = document.createElement('nav');pagination.className='pagination';pagination.setAttribute('aria-label','Paginação de moradores');lista.after(pagination);
function renderizarLista(rows) {
    lista.replaceChildren();
    if(!rows.length){const p=document.createElement('p');p.className='empty';p.textContent='Nenhum morador encontrado.';lista.append(p);return;}
    for(const row of rows) {
        const card=document.createElement('div');card.className='morador';
        const canEdit=document.body.dataset.perfil==='administracao';
        const name=document.createElement(canEdit?'a':'span');name.className='morador-nome';name.textContent=row.nome;card.append(name);
        if(canEdit) {
            name.href='../telasCadastro/cadastro_morador.php?id='+row.id;
            card.addEventListener('click',event=>{if(!event.target.closest('a,button,input,select'))window.location.assign(name.href);});
        }
        const actions=document.createElement('div');actions.className='morador-direita';const ap=document.createElement('span');ap.textContent='Apto '+row.apartamento;actions.append(ap);
        for(const [type,label,image] of [['encomenda','Cadastrar encomenda','box'],['veiculo','Cadastrar veículo','carro'],['visitante','Cadastrar visitante','user']]) {
            const link=document.createElement('a');link.className='btn-'+type;link.title=label;
            link.href='../telasCadastro/cadastro_'+type+'.php?'+new URLSearchParams({morador_id:row.id,bloco:row.bloco,apartamento:row.apartamento});
            const img=document.createElement('img');img.src='../../assets/images/'+image+'.png';img.width=20;img.height=20;img.alt=label;link.append(img);actions.append(link);
        }
        card.append(actions);lista.append(card);
    }
}
async function carregarMoradores() {
    if(!blocoAtivo)return;
    const version=++requestVersion;lista.setAttribute('aria-busy','true');
    try {
        const response=await fetch('../../crud/getmoradores.php?'+new URLSearchParams({bloco:blocoAtivo,pagina:paginaMoradores,busca:document.getElementById('buscaMoradorInput').value.trim()}));
        const data=await response.json();if(version!==requestVersion)return;
        if(!response.ok)throw new Error(data.message||'Falha ao consultar moradores.');
        renderizarLista(data.moradores);pagination.replaceChildren();
        const label=document.createElement('span');label.textContent='Página '+paginaMoradores;pagination.append(label);
        for(const [label,step,enabled] of [['Anterior',-1,paginaMoradores>1],['Próxima',1,data.proxima]]) {
            if(!enabled)continue;const button=document.createElement('button');button.textContent=label;button.onclick=()=>{paginaMoradores+=step;carregarMoradores();};pagination.append(button);
        }
    } catch(error){if(version===requestVersion){lista.textContent=error.message;pagination.replaceChildren();}}
    finally{if(version===requestVersion)lista.removeAttribute('aria-busy');}
}
function filtrarMoradores(){paginaMoradores=1;clearTimeout(searchTimer);searchTimer=setTimeout(carregarMoradores,200);}
function selecionarBloco(block) {
    blocoAtivo=block;paginaMoradores=1;
    document.querySelectorAll('.bloco').forEach((el,i)=>{el.classList.toggle('ativo',i+1===block);el.setAttribute('aria-pressed',String(i+1===block));});
    const addButton=document.getElementById('btnAdicionarMorador');
    if(addButton)addButton.href='../telasCadastro/cadastro_morador.php?bloco='+block;
    document.getElementById('buscaMoradorInput').value='';carregarMoradores();
}
document.getElementById('buscaMoradorInput').addEventListener('input',filtrarMoradores);
document.getElementById('buscaMoradorInput').addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();filtrarMoradores();}});
document.querySelectorAll('.bloco').forEach(el=>{el.tabIndex=0;el.setAttribute('role','button');el.setAttribute('aria-pressed','false');el.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();el.click();}});});
