// External library dependencies.
import React from 'react';
import metadata from './module.json'; 

// CRITICAL: Ensure you import your styles if they are bundled via Webpack
// Uncomment this line if your CSS source file exists here:
// import '../../includes/assets/css/style.css'; 

// --- ROBUST GLOBAL DESTRUCTURING ---
// Safely assign global objects to variables.
const DiviModule = window?.divi?.module || {};

const {
  ModuleContainer,
  StyleContainer,
  elementClassnames,
} = DiviModule;


// --- 1. Style Component ---
const ModuleStyles = ({ attrs, elements, settings, noStyleTag }) => (
  <StyleContainer noStyleTag={noStyleTag}>
    {elements.style({ attrName: 'module' })}
  </StyleContainer>
);

/**
 * Function for registering module classnames.
 */
const moduleClassnames = ({ classnamesInstance, attrs }) => {
  // FIX: elementClassnames is now safely available from the global destructuring
  classnamesInstance.add(elementClassnames({ attrs: attrs?.module?.decoration ?? {} }));
}

/**
 * Noka Gallery Module Object (Stateless, Tutorial Style).
 * This component handles the Visual Builder preview.
 */
const NokaGalleryModule = {
  // Metadata that is used on Visual Builder and Frontend
  metadata,

  // Layout renderer components.
  renderers: {
    // This is the component that runs in the Visual Builder iframe
    edit: ({ attrs, id, name, elements }) => {
      // Logic for Visual Builder Preview (simplified)
      const galleryId = attrs?.gallery_select?.innerContent?.desktop?.value; 

      // Check if the value is Dynamic Content (starts with @)
      const isDynamic = galleryId && typeof galleryId === 'string' && galleryId.startsWith('@');

      let previewContent;
      
      if (!galleryId || galleryId === 'none') {
        previewContent = (
            <div className="noka-placeholder-vb" style={{padding:'20px', textAlign:'center', border:'1px dashed #DDD'}}>
                Select a Noka Gallery in the settings sidebar.
            </div>
        );
      } else {
        const displayText = isDynamic ? "Dynamic Gallery Selected" : `Gallery ID ${galleryId} Selected`;
        
        previewContent = (
            <div className="noka-loading-vb" style={{padding:'20px', textAlign:'center', border:'1px dashed #7AA', minHeight:'100px'}}>
                {displayText} <br/>
                <small>(Front-End Rendered by PHP Shortcode)</small>
            </div>
        );
      }

      return (
        <ModuleContainer
          attrs={attrs}
          elements={elements}
          id={id}
          moduleClassName="noka_gallery_module"
          name={name}
          stylesComponent={ModuleStyles}
          classnamesFunction={moduleClassnames}
        >
          {elements.styleComponents({ attrName: 'module' })}
          <div className="et_pb_module_inner">
            {previewContent}
          </div>
        </ModuleContainer>
      );
    },
  },

  // Placeholder content for new modules
  placeholderContent: {
    module: {
      decoration: {
        background: {
          desktop: { value: { color: '#ffffff' } }
        }
      }
    },
    gallery_select: {
      innerContent: {
        desktop: { value: 'none' }
      }
    }
  },
};

// --- MODULE REGISTRATION ---

// 1. Get addAction from WordPress hooks (Global variable)
const { addAction } = window?.vendor?.wp?.hooks || {};

// 2. Get registerModule from Divi (Global variable)
const { registerModule } = window?.divi?.moduleLibrary || {};

if (addAction && registerModule) {
    // 3. Register the module ONLY after Divi's library store is ready.
    addAction('divi.moduleLibrary.registerModuleLibraryStore.after', 'noka.galleryModule', () => {
        registerModule(NokaGalleryModule.metadata, NokaGalleryModule);
    });
    console.log('SUCCESS: Noka Gallery Registration Hook Added!');
} else {
    // This should only happen if the PHP dependency logic is wrong.
    console.error('ERROR: Divi registration object or wp-hooks not found. Module registration failed.');
}

// Ensure the source file is built after this change.

export default NokaGalleryModule;