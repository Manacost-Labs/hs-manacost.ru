import{s,t as k,v as _}from"./app-core.c8323d51.js";import{h as c}from"./utils.4347bc5f.js";import{A as d}from"./icon.ba2c22aa.js";import{_ as f}from"./vendor-other.39e59221.js";import"./vendor-vue-ui.232c7c36.js";import"./vendor-lodash.03129cfe.js";const{addFilter:h}=window.wp.hooks,{BlockControls:p}=window.wp.blockEditor,{Button:l,ToolbarGroup:$,ToolbarButton:x}=window.wp.components,{Fragment:b,render:y,unmountComponentAtNode:w}=window.wp.element,{createHigherOrderComponent:A}=window.wp.compose,{select:u,useSelect:B}=window.wp.data,I="all-in-one-seo-pack",m={generateWithAI:f("Generate with AI",I),editWithAI:f("Edit with AI",I)};let S=!1;const g=(i,n={})=>{window.aioseoBus.$emit("do-post-settings-main-tab-change",{name:"aiContent"}),i.classList.add("is-busy"),i.disabled=!0;const e=_(),t=s();setTimeout(()=>{t.initiator=n?.initiator,(!t.initiator||!t.initiator.slug)&&t.resetInitiator(),e.isModalOpened="image-generator",i.classList.remove("is-busy"),i.disabled=!1},500)},L=()=>{s().extend.imageBlockToolbar&&(S||(h("editor.BlockEdit","aioseo/extend-image-block-toolbar",A(n=>e=>{const t=e.name==="core/image"&&e.attributes?.url,r=B(o=>!t||!e.attributes?.id?null:o("core").getEntityRecord("postType","attachment",e.attributes.id)||null,[`media-${e.attributes.id}`]);return t?c`
				<${b}>
					<${p}>
						<${$}>
							<${x}
								icon=${d}
								iconSize=${24}
								label=${m.editWithAI}
								onClick=${o=>{g(o.currentTarget,{initiator:{slug:"image-block-toolbar",wpMedia:r}})}}
								style=${{maxHeight:"90%",alignSelf:"center",padding:"0"}}
							/>
						</${$}>
					</${p}>

					<${n} ...${e} />
				</${b}>`:c`<${n} ...${e} />`},"extendImageBlockToolbar")),S=!0))},N=()=>{if(!s().extend.imageBlockPlaceholder)return;const n=u("core/block-editor").getSelectedBlock();if(!n||n.name!=="core/image"||n.attributes?.url)return;const t=k().getElementById(`block-${n.clientId}`),r=t?.querySelector(".components-form-file-upload");if(!r||t?.querySelector(".aioseo-ai-image-generator-btn"))return;const o=document.createElement("div");y(c`
			<${l}
				className=${"aioseo-ai-image-generator-btn"}
				variant=${"secondary"}
				icon=${d}
				iconSize=${"20"}
				__next40pxDefaultSize=${!0}
			>
				${m.generateWithAI}
			</${l}>`,o);const a=o.firstChild?.cloneNode(!0);a&&(r.after(a),a.addEventListener("click",()=>{g(a,{initiator:{slug:"image-block-placeholder"}})})),w(o),o.remove()},W=()=>{if(!s().extend.featuredImageButton||u("core/edit-post").getActiveGeneralSidebarName()!=="edit-post/document")return;if(u("core/editor").getEditedPostAttribute("featured_media")){document.querySelector(".aioseo-ai-image-generator-btn-featured-image")?.remove();return}setTimeout(()=>{const e=document.querySelector(".editor-post-featured-image__container"),t=e?.querySelector("button");if(!t||e?.querySelector(".aioseo-ai-image-generator-btn-featured-image"))return;e.style.display="flex",e.style.gap="8px";const r=document.createElement("div");y(c`
				<${l}
					className=${"aioseo-ai-image-generator-btn-featured-image"}
					variant=${"secondary"}
					icon=${d}
					iconSize=${"20"}
					__next40pxDefaultSize=${!0}
					title=${m.generateWithAI}
				/>`,r);const o=r.firstChild?.cloneNode(!0);o&&(t.after(o),o.addEventListener("click",()=>{g(o,{initiator:{slug:"featured-image-btn"}})})),w(r),r.remove()})};export{W as extendFeaturedImageButton,N as extendImageBlockPlaceholder,L as extendImageBlockToolbar};
