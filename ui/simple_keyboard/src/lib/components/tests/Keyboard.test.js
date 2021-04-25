import Keyboard from '../Keyboard';
import { setDOM, clearDOM, testLayoutStdButtons, iterateButtons, triggerDocumentPointerUp, triggerDocumentPointerDown } from '../../../utils/TestUtility';

it('Keyboard will not render without target element', () => {
  try {
    new Keyboard();
    expect(true).toBe(false);
  } catch (e) {
    expect(e.message).toBe("KEYBOARD_DOM_ERROR");
  }
});

it('Keyboard will run without options', () => {
  // Prepare target DOM element
  setDOM();

  // No options
  new Keyboard();
});

it('Keyboard will run with empty options', () => {
  // Prepare target DOM element
  setDOM();

  // No options
  new Keyboard({});
});

it('Keyboard will run with custom DOM target', () => {
  setDOM("myTestDiv");

  new Keyboard(".myTestDiv");
  expect(document.body.querySelector(".myTestDiv")).toBeDefined();
});

it('Keyboard will run with debug option set', () => {
  setDOM();
  
  const keyboard = new Keyboard({
    debug: true
  });

  expect(keyboard.options.debug).toBeTruthy();
});

it('Keyboard will use touch events', () => {
  let touched = false

  clearDOM();

  document.body.innerHTML = `
    <div class="keyboard"></div>
  `;

  const keyboard = new Keyboard('.keyboard', {
    useTouchEvents: true,
    onChange: () => touched = true,
    layout: {
      default: ["q"]
    }
  });

  keyboard.getButtonElement("q").ontouchstart();
  keyboard.getButtonElement("q").ontouchend();
  keyboard.getButtonElement("q").ontouchcancel();
  
  expect(keyboard.options.useTouchEvents).toBeTruthy();
  expect(touched).toBeTruthy();
  expect(keyboard.getInput()).toBe('q');
})

it('Keyboard standard buttons will work', () => {
  setDOM();
  const keyboard = new Keyboard({
    maxLength: {
      "default": 10
    }
  });
  
  testLayoutStdButtons(keyboard);
});


it('Keyboard shift buttons will work', () => {
  setDOM();
  const keyboard = new Keyboard();

  keyboard.setOptions({
    layoutName: "shift",
    maxLength: 42
  });

  testLayoutStdButtons(keyboard);
});

it('Keyboard setOptions will work without a param', () => {
  setDOM();
  const keyboard = new Keyboard();

  keyboard.setOptions();
});

it('Keyboard empty buttons wont do anything as expected', () => {
  setDOM();

  const keyboard = new Keyboard({
    layout: {
      default: [
        "{//} {button} d",
        "a b c d e f g h i j",
      ]
    }
  });

  keyboard.getButtonElement("{//}").onclick();
});

it('Keyboard onKeyPress will work', () => {
  setDOM();

  let pressed = false;

  const keyboard = new Keyboard({
    onKeyPress: () => {
      pressed = true;
    },
    debug: true
  });

  keyboard.getButtonElement("q").onclick();

  expect(pressed).toBeTruthy();
});

it('Keyboard standard function buttons will not change input', () => {
  setDOM();

  const keyboard = new Keyboard({
    useButtonTag: true
  });

  iterateButtons((button) => {
    if(button.getAttribute("data-skbtn") === "{shift}"){
      button.onclick();
    }
  });

  expect(keyboard.getInput()).toBeFalsy();
});

it('Keyboard syncInstanceInputs will work', () => {
  clearDOM();

  document.body.innerHTML = `
    <div class="keyboard1"></div>
    <div class="keyboard2"></div>
  `;

  const sharedOptions = {
    syncInstanceInputs: true
  };

  let keyboard1 = new Keyboard(".keyboard1", sharedOptions);
  let keyboard2 = new Keyboard(".keyboard2", sharedOptions);

  keyboard1.getButtonElement("q").onclick();

  expect(keyboard2.getInput()).toBe("q");

  /**
   * Test cursor syncing...
   * Reinit keyboards
   */
  keyboard1 = new Keyboard(".keyboard1", sharedOptions);
  keyboard2 = new Keyboard(".keyboard2", sharedOptions);

  keyboard1.getButtonElement("1").onclick();
  keyboard1.getButtonElement("5").onclick();
  keyboard1.getButtonElement("6").onclick();

  keyboard1.setCaretPosition(1);

  keyboard1.getButtonElement("2").onclick();
  keyboard1.getButtonElement("3").onclick();
  keyboard1.getButtonElement("4").onclick();

  expect(keyboard1.getInput()).toBe("123456");
  expect(keyboard2.getInput()).toBe("123456");
});

it('Keyboard onChange will work', () => {
  setDOM();

  let output = false;

  const keyboard = new Keyboard({
    onChange: (input) => {
      output = input;
    },
    useMouseEvents: true
  });

  keyboard.getButtonElement("q").onclick();

  expect(output).toBe("q");
});

it('Keyboard onChangeAll will work', () => {
  setDOM();

  let output;

  const keyboard = new Keyboard({
    onChangeAll: (input) => {
      output = input ? input.default : null;
    },
    useMouseEvents: true
  });

  keyboard.getButtonElement("q").onclick();

  expect(output).toBe("q");
});

it('Keyboard clearInput will work', () => {
  setDOM();

  const keyboard = new Keyboard();

  /**
   * Avoid setInput for this test
   */
  keyboard.input = {
    "default": "hello"
  };

  keyboard.clearInput();

  expect(keyboard.getInput()).toBeFalsy();
});

it('Keyboard clearInput will work with syncInstanceInputs', () => {
  clearDOM();

  document.body.innerHTML = `
    <div class="keyboard1"></div>
    <div class="keyboard2"></div>
  `;

  const sharedOptions = {
    syncInstanceInputs: true
  };

  const keyboard1 = new Keyboard(".keyboard1", sharedOptions);
  const keyboard2 = new Keyboard(".keyboard2", sharedOptions);

  /**
   * Avoid setInput for this test
   */
  keyboard1.input = {
    "default": "hello"
  };

  keyboard2.clearInput();

  expect(keyboard1.getInput()).toBeFalsy();
});

it('Keyboard setInput will work', () => {
  setDOM();

  const keyboard = new Keyboard();

  keyboard.setInput("hello");

  expect(keyboard.getInput()).toBe("hello");
});

it('Keyboard setInput will work with syncInstanceInputs', () => {
  clearDOM();

  document.body.innerHTML = `
    <div class="keyboard1"></div>
    <div class="keyboard2"></div>
  `;

  const sharedOptions = {
    syncInstanceInputs: true
  };

  const keyboard1 = new Keyboard(".keyboard1", sharedOptions);
  const keyboard2 = new Keyboard(".keyboard2", sharedOptions);

  keyboard1.setInput("hello");

  expect(keyboard2.getInput()).toBe("hello");
});

it('Keyboard dispatch will work', () => {
  setDOM();

  document.body.innerHTML = `
    <div class="keyboard1"></div>
    <div class="keyboard2"></div>
  `;

  const keyboard1 = new Keyboard(".keyboard1");
  const keyboard2 = new Keyboard(".keyboard2");

  keyboard1.dispatch(instance => {
    instance.setOptions({
      buttonTheme: [
        {
          class: "myCustomClass",
          buttons: "Q"
        }
      ]
    })
  });

  expect(keyboard2.options.buttonTheme[0].class).toBe("myCustomClass");
});

it('Keyboard dispatch will not work without SimpleKeyboardInstances', () => {
  setDOM();

  document.body.innerHTML = `
    <div class="keyboard1"></div>
    <div class="keyboard2"></div>
  `;

  const keyboard1 = new Keyboard(".keyboard1");
  new Keyboard(".keyboard2");

  window['SimpleKeyboardInstances'] = null;

  try {
    keyboard1.dispatch(instance => {
      instance.setOptions({
        buttonTheme: [
          {
            class: "myCustomClass",
            buttons: "Q"
          }
        ]
      })
    });

    expect(true).toBe(false);
  } catch (e) {
    expect(e.message).toBe("INSTANCES_VAR_ERROR");
  }
});

it('Keyboard addButtonTheme will work', () => {
  setDOM();

  const keyboard = new Keyboard();
  keyboard.addButtonTheme("q", "test");

  expect(keyboard.options.buttonTheme[0].class).toBe("test");
});

it('Keyboard addButtonTheme will not work without params', () => {
  setDOM();

  const keyboard = new Keyboard();
  const returnVal = keyboard.addButtonTheme();

  expect(returnVal).toBeFalsy();
});

it('Keyboard addButtonTheme will amend a buttonTheme', () => {
  setDOM();

  const keyboard = new Keyboard({
    buttonTheme: [
      {
        class: "test",
        buttons: "s"
      }
    ]
  });

  keyboard.addButtonTheme("q", "test");

  expect(keyboard.options.buttonTheme[0].class).toBe("test");
});

it('Keyboard addButtonTheme will create a buttonTheme', () => {
  setDOM();

  const keyboard = new Keyboard({
    buttonTheme: [
      {
        class: "blurb",
        buttons: "s"
      }
    ]
  });

  keyboard.addButtonTheme("q", "test");

  expect(keyboard.options.buttonTheme[1].class).toBe("test");
});

it('Keyboard addButtonTheme will ignore a repeated buttonTheme', () => {
  setDOM();

  const keyboard = new Keyboard({
    buttonTheme: [
      {
        class: "test",
        buttons: "s a"
      }
    ]
  });

  keyboard.addButtonTheme("a", "test");

  expect(keyboard.options.buttonTheme[0].buttons).toBe("s a");
});

it('Keyboard addButtonTheme will amend a buttonTheme', () => {
  setDOM();

  const keyboard = new Keyboard({
    buttonTheme: [
      {
        class: "test",
        buttons: "s"
      }
    ]
  });

  keyboard.addButtonTheme("q", "test");

  expect(keyboard.options.buttonTheme[0].buttons).toBe("s q");
});


it('Keyboard removeButtonTheme without params will remove all button themes', () => {
  setDOM();

  const keyboard = new Keyboard({
    buttonTheme: [
      {
        class: "test",
        buttons: "s"
      }
    ]
  });

  keyboard.removeButtonTheme();

  expect(keyboard.options.buttonTheme.length).toBe(0);
});


it('Keyboard removeButtonTheme will work', () => {
  setDOM();

  const keyboard = new Keyboard({
    buttonTheme: [
      {
        class: "test",
        buttons: "s"
      }
    ]
  });

  keyboard.removeButtonTheme("s", "test");

  expect(keyboard.options.buttonTheme.length).toBe(0);
});

it('Keyboard removeButtonTheme will work wihtout a class', () => {
  setDOM();

  const keyboard = new Keyboard({
    buttonTheme: [
      {
        class: "test",
        buttons: "s"
      }
    ]
  });

  keyboard.removeButtonTheme("s");

  expect(keyboard.options.buttonTheme.length).toBe(0);
});

it('Keyboard removeButtonTheme will do nothing without a button param', () => {
  setDOM();

  const keyboard = new Keyboard({
    buttonTheme: [
      {
        class: "test",
        buttons: "s"
      }
    ]
  });

  keyboard.removeButtonTheme(null, "test");

  expect(keyboard.options.buttonTheme.length).toBe(1);
});

it('Keyboard removeButtonTheme does nothing if req button doesnt have a buttonTheme', () => {
  setDOM();

  const keyboard = new Keyboard({
    buttonTheme: [
      {
        class: "test",
        buttons: "a"
      }
    ]
  });

  keyboard.removeButtonTheme("s", "test");

  expect(keyboard.options.buttonTheme.length).toBe(1);
});

it('Keyboard removeButtonTheme does nothing if buttonTheme class does not exist', () => {
  setDOM();

  const keyboard = new Keyboard({
    buttonTheme: [
      {
        class: "testy",
        buttons: "a"
      }
    ]
  });

  keyboard.removeButtonTheme("a", "test");

  expect(keyboard.options.buttonTheme.length).toBe(1);
});

it('Keyboard removeButtonTheme does nothing if buttonTheme doesnt have the requested buttons', () => {
  setDOM();

  const keyboard = new Keyboard({
    buttonTheme: [
      {
        class: "test",
        buttons: "a b c d e f"
      }
    ]
  });

  keyboard.removeButtonTheme("g", "test");

  expect(keyboard.options.buttonTheme[0].buttons).toBe("a b c d e f");
});

it('Keyboard getButtonElement will not return anything if empty match', () => {
  setDOM();

  const keyboard = new Keyboard({
    layout: {
      default: [
        "{//} {button} d",
        "a b c d e f g h i j",
      ]
    }
  });

  expect(keyboard.getButtonElement("{waldo}")).toBeFalsy();
});

it('Keyboard getButtonElement will return multiple matched buttons', () => {
  setDOM();

  const keyboard = new Keyboard();

  expect(keyboard.getButtonElement("{shift}").length).toBe(2);
});

it('Keyboard will receive physical keyboard events', () => {
  setDOM();

  new Keyboard({
    debug: true,
    physicalKeyboardHighlight: true
  });

  document.onkeyup({
    charCode: 0,
    code: "KeyF",
    key: "f",
    which: 70,
    target: document.createElement('input')
  });
});

it('Keyboard caretEventHandler will detect input, textarea focus', () => {
  setDOM();

  const keyboard = new Keyboard();
  const myInput = document.createElement('input');

  keyboard.caretEventHandler({
    charCode: 0,
    code: "KeyF",
    key: "f",
    which: 70,
    target: myInput
  });

  expect(keyboard.getCaretPosition()).toBe(0);
});

it('Keyboard caretEventHandler will not set caretPosition on disableCaretPositioning', () => {
  setDOM();

  const keyboard = new Keyboard();
  const myInput = document.createElement('input');

  keyboard.caretEventHandler({
    charCode: 0,
    code: "KeyF",
    key: "f",
    which: 70,
    target: myInput
  });

  expect(keyboard.getCaretPosition()).toBe(0);

  keyboard.setOptions({
    disableCaretPositioning: true
  });

  keyboard.caretEventHandler({
    charCode: 0,
    code: "KeyF",
    key: "f",
    which: 70,
    target: myInput
  });

  expect(keyboard.getCaretPosition()).toBe(null);
});

it('Keyboard caretEventHandler ignore positioning if input, textarea is blur', () => {
  setDOM();

  const keyboard = new Keyboard();

  keyboard.isMouseHold = true;

  keyboard.caretEventHandler({
    charCode: 0,
    code: "KeyF",
    key: "f",
    which: 70,
    target: document.createElement('div')
  });

  expect(keyboard.getCaretPosition()).toBeFalsy();
});

it('Keyboard caretEventHandler will work with debug', () => {
  setDOM();

  const keyboard = new Keyboard({
    debug: true
  });

  keyboard.input.default = "hello";
  keyboard.setCaretPosition(2)

  expect(keyboard.getCaretPosition()).toBe(2);

  const myInput = document.createElement('input');

  keyboard.caretEventHandler({
    charCode: 0,
    code: "KeyF",
    key: "f",
    which: 70,
    target: myInput
  });

  expect(keyboard.getCaretPosition()).toBe(0);
});

it('Keyboard onInit will work', () => {
  setDOM();

  let passed = false;

  new Keyboard({
    onInit: () => {
      passed = true
    }
  });

  expect(passed).toBeTruthy();
});

it('Keyboard onRender will work', () => {
  setDOM();

  let passed = false;

  new Keyboard({
    onRender: () => {
      passed = true
    }
  });

  expect(passed).toBeTruthy();
});

it('Keyboard buttonTheme that is invalid will be ignored and not throw', () => {
  setDOM();

  new Keyboard({
    buttonTheme: [
      {
        class: null,
        buttons: null
      }
    ]
  });
});

it('Keyboard buttonTheme buttons that are invalid will be ignored and not throw', () => {
  setDOM();

  new Keyboard({
    buttonTheme: [
      {
        class: null,
        buttons: undefined
      }
    ]
  });
});

it('Keyboard buttonTheme will be ignored if buttons param not a string', () => {
  setDOM();

  new Keyboard({
    buttonTheme: [
      {
        class: "test",
        buttons: {
          wrong: true
        }
      }
    ]
  });
});

it('Keyboard buttonTheme will be ignored if already added', () => {
  setDOM();

  new Keyboard({
    buttonTheme: [
      {
        class: "test",
        buttons: "a b c"
      },
      {
        class: "test",
        buttons: "c"
      },
      {
        class: "anotherclass",
        buttons: "c"
      },
      {
        class: "yetAnotherclass",
        buttons: "c"
      },
      {
        class: "anotherclass",
        buttons: "c"
      },
    ]
  });
});

it('Keyboard can set a module', () => {
  setDOM();

  const keyboard = new Keyboard();

  keyboard.registerModule(
    "test",
    (module) => {
      module.foo = "bar";
    }
  );

  expect(keyboard.getModuleProp("test", "foo")).toBe("bar");
});

it('Keyboard registerModule will return current module tree', () => {
  setDOM();

  const keyboard = new Keyboard();

  keyboard.modules.test = {
    testy: "test"
  };

  keyboard.registerModule(
    "test",
    (module) => {
      module.foo = "bar";
    }
  );

  expect(keyboard.getModuleProp("test", "testy")).toBe("test");
  expect(keyboard.getModuleProp("test", "foo")).toBe("bar");
});

it('Keyboard can set a module by amending the modules tree', () => {
  setDOM();

  const keyboard = new Keyboard();

  keyboard.modules = {
    testman: {
      foo: "baz"
    }
  };

  keyboard.registerModule(
    "test",
    (module) => {
      module.foo = "bar";
    }
  );

  expect(keyboard.getModuleProp("test", "foo")).toBe("bar");
});

it('Keyboard will not retrieve an option for an inexistent module', () => {
  setDOM();

  const keyboard = new Keyboard();

  expect(keyboard.getModuleProp("test", "foo")).toBeFalsy();
});

it('Keyboard will get a list of modules', () => {
  setDOM();

  const keyboard = new Keyboard();

  keyboard.registerModule(
    "test",
    (module) => {
      module.foo = "bar";
    }
  );

  expect(keyboard.getModulesList()[0]).toBe("test");
});

it('Keyboard loadModules will load a simple module', () => {
  setDOM();

  class myClass {
    init = (module) => {
      module.foo = "bar";
    };
  }

  new Keyboard({
    modules: [
      myClass
    ]
  });  
});

it('Keyboard handleButtonMouseUp will set isMouseHold to false', () => {
  setDOM();

  const keyboard = new Keyboard();

  keyboard.isMouseHold = true;

  document.onmouseup({
    target: document.body
  });

  expect(keyboard.isMouseHold).toBeFalsy();
});

it('Keyboard handleButtonMouseUp clear holdInteractionTimeout', () => {
  setDOM();

  const keyboard = new Keyboard();

  keyboard.isMouseHold = true;
  keyboard.holdInteractionTimeout = setTimeout(() => {}, 10000);

  document.onmouseup({
    target: document.body
  });
});

it('Keyboard handleButtonMouseDown will work', () => {
  setDOM();

  const keyboard = new Keyboard({ useMouseEvents: true });

  console.log(keyboard.getButtonElement("q"))
  keyboard.getButtonElement("q").onclick();

  document.onmouseup({
    target: document.body
  });

});

it('Keyboard handleButtonMouseDown will work with preventMouseDownDefault', () => {
  setDOM();

  const keyboard = new Keyboard();

  keyboard.options.preventMouseDownDefault = true;

  keyboard.handleButtonMouseDown("q", {
    target: keyboard.getButtonElement("q"),
    preventDefault: () => {},
    stopPropagation: () => {}
  });

  keyboard.getButtonElement("q").onclick();
  document.onmouseup({
    target: document.body
  });

});

it('Keyboard onModulesLoaded will work', () => {
  setDOM();

  class myClass {
    init = (module) => {
      module.foo = "bar";
    };
  }

  let foo;

  new Keyboard({
    modules: [
      myClass
    ],
    onModulesLoaded: () => {
      foo = "bar";
    }
  });

  expect(foo).toBe("bar");
});

it('Keyboard inputPattern will work globally', () => {
  setDOM();

  const keyboard = new Keyboard({
    inputPattern: /^\d+$/,
    useMouseEvents: true
  });

  keyboard.getButtonElement("q").onclick();

  expect(keyboard.getInput()).toBeFalsy();

  keyboard.getButtonElement("1").onclick();

  expect(keyboard.getInput()).toBe("1");
});

it('Keyboard inputPattern will work by input name', () => {
  setDOM();

  const keyboard = new Keyboard({
    debug: true,
    inputName: "test1",
    inputPattern: {
      test1: /^\d+$/
    },
    useMouseEvents: true
  });

  keyboard.getButtonElement("q").onclick();
  keyboard.getButtonElement("1").onclick();

  expect(keyboard.getInput()).toBe("1");

  keyboard.setOptions({
    inputName: "default"
  });

  keyboard.getButtonElement("q").onclick();
  keyboard.getButtonElement("1").onclick();

  expect(keyboard.getInput()).toBe("q1");
});

it('Keyboard processAutoTouchEvents will work', () => {
  setDOM();

  navigator.maxTouchPoints = true;

  const keyboard = new Keyboard({
    autoUseTouchEvents: true
  });

  expect(keyboard.options.useTouchEvents).toBeTruthy();
});

it('Keyboard processAutoTouchEvents will work with debugging enabled', () => {
  setDOM();

  navigator.maxTouchPoints = true;

  const keyboard = new Keyboard({
    autoUseTouchEvents: true,
    debug: true
  });

  expect(keyboard.options.useTouchEvents).toBeTruthy();
});

it('Keyboard beforeFirstRender method will work', () => {
  setDOM();

  let timesCalled = 0;

  const keyboard = new Keyboard({
    beforeFirstRender: () => {
      timesCalled++;
    }
  });

  /**
   * Triggering another render
   */
  keyboard.setOptions({
    layoutName: "shift"
  });

  expect(timesCalled).toBe(1);
});

it('Keyboard beforeFirstRender will show PointerEvents warning', () => {
  setDOM();

  let timesCalled = 0;

  window.PointerEvent = window.PointerEvent ? window.PointerEvent : () => {};

  new Keyboard({
    debug: true,
    beforeFirstRender: () => {
      timesCalled++;
    }
  });

  expect(timesCalled).toBe(1);
});

it('Keyboard beforeRender method will work', () => {
  setDOM();

  let timesCalled = 0;

  const keyboard = new Keyboard({
    beforeRender: () => {
      timesCalled++;
    }
  });

  /**
   * Triggering another render
   */
  keyboard.setOptions({
    layoutName: "shift"
  });

  expect(timesCalled).toBe(2);
});

it('Keyboard parseRowDOMContainers will work', () => {
  setDOM();

  const keyboard = new Keyboard({
    layout: {
      'default': [
        '` [1 2 3 4 5 6 7 8 9] 0 - = {bksp}',
        '{tab} q w e r t y u [i o p] [ ] \\',
        '{lock} [a s d] f g h j k l ; \' {enter}',
        '{shift} z x c v b n m , . / {shift}',
        '[.com @] {space} {arrowleft} [{arrowup} {arrowdown}] {arrowright}'
      ],
      'shift': [
        '~ ! @ # $ % ^ & * ( ) _ + {bksp}',
        '{tab} [Q W E R T] Y U I O P { } |',
        '{lock} A S D F G H J K L : " {enter}',
        '{shift} Z X C V [B N M <] > ? {shift}',
        '.com @ {space}'
      ]
    }
  });

  const containers = Array.from(document.querySelectorAll(".hg-button-container"));

  expect(containers.length).toBe(5);

  keyboard.setOptions({
    debug: true
  });

  expect(containers.length).toBe(5);
});

it('Keyboard parseRowDOMContainers will ignore empty rows', () => {
  setDOM();

  let failed = false;

  try {
    const keyboard = new Keyboard();
    keyboard.parseRowDOMContainers({
      children: []
    });
  } catch (e) {
    failed = true;
  }

  expect(failed).toBeFalsy();
});


it('Keyboard parseRowDOMContainers will ignore missing endIndex or endIndex before startIndex', () => {
  setDOM();

  new Keyboard({
    layout: {
      'default': [
        '` [1 2 3 4 5 6 7 8 9 0 - = {bksp}',
        '` 1 2 3] 4 5 6 7 8 9 [0 - = {bksp}',
      ]
    }
  });

  const containers = Array.from(document.querySelectorAll(".hg-button-container"));

  expect(containers.length).toBe(0);
});

it('Keyboard disableRowButtonContainers will bypass parseRowDOMContainers', () => {
  setDOM();

  new Keyboard({
    disableRowButtonContainers: true,
    layout: {
      'default': [
        '` [1 2 3 4 5 6 7 8 9] 0 - = {bksp}',
        '{tab} q w e r t y u [i o p] [ ] \\',
        '{lock} [a s d] f g h j k l ; \' {enter}',
        '{shift} z x c v b n m , . / {shift}',
        '[.com @] {space} {arrowleft} [{arrowup} {arrowdown}] {arrowright}'
      ],
      'shift': [
        '~ ! @ # $ % ^ & * ( ) _ + {bksp}',
        '{tab} [Q W E R T] Y U I O P { } |',
        '{lock} A S D F G H J K L : " {enter}',
        '{shift} Z X C V [B N M <] > ? {shift}',
        '.com @ {space}'
      ]
    }
  });

  const containers = Array.from(document.querySelectorAll(".hg-button-container"));

  expect(containers.length).toBe(0);
});

it('Keyboard inputName change will trigget caretPosition reset', () => {
  setDOM();

  const keyboard = new Keyboard();

  keyboard.setCaretPosition(0);

  keyboard.getButtonElement("q").onpointerdown();
  keyboard.getButtonElement("1").onpointerdown();

  expect(keyboard.getCaretPosition()).toBe(2);

  keyboard.setOptions({
    inputName: "myInput"
  });

  keyboard.getButtonElement("q").onpointerdown();
  keyboard.getButtonElement("1").onpointerdown();
  keyboard.getButtonElement("b").onpointerdown();

  expect(keyboard.getCaretPosition()).toBe(null);
});

it('Keyboard destroy will work', () => {
  setDOM();

  const keyboard = new Keyboard();
  keyboard.destroy();
  expect(keyboard.keyboardDOM.innerHTML).toBe("");

  expect(document.onkeydown).toBe(null);
  expect(document.onkeyup).toBe(null);

  expect(document.onpointerdown).toBe(null);
  expect(document.onpointerup).toBe(null);

  expect(document.onmousedown).toBe(null);
  expect(document.onmouseup).toBe(null);

  expect(document.ontouchstart).toBe(null);
  expect(document.ontouchend).toBe(null);
  expect(document.ontouchcancel).toBe(null);

  expect(keyboard.initialized).toBe(false);
});

it('Keyboard destroy will work with debug option', () => {
  setDOM();

  const keyboard = new Keyboard({ debug: true });
  keyboard.destroy();
  expect(keyboard.keyboardDOM.innerHTML).toBe("");
});

it('Keyboard disableButtonHold will work', () => {
  setDOM();

  const keyboard = new Keyboard({
    disableButtonHold: true
  });

  expect(keyboard.options.disableButtonHold).toBe(true);
});

it('Keyboard caretEventHandler will be triggered on mouseup and ontouchend', () => {
  setDOM();

  const keyboard = new Keyboard({
    disableCaretPositioning: true
  });

  keyboard.setCaretPosition(6);

  expect(keyboard.getCaretPosition()).toBe(6);

  const event = {
    target: document.body
  };

  triggerDocumentPointerUp(event);

  expect(keyboard.getCaretPosition()).toBe(null);

  keyboard.setOptions({
    disableCaretPositioning: false
  })

  keyboard.setCaretPosition(10);
});

it('Keyboard onKeyReleased will work', () => {
  setDOM();

  let pressed = false;
  let firedTimes = 0;
  let buttonPressed;

  const keyboard = new Keyboard({
    onKeyReleased: button => {
      pressed = true;
      buttonPressed = button;
      firedTimes++;
    },
    debug: true
  });

  keyboard.getButtonElement("q").onpointerdown();
  keyboard.getButtonElement("q").onpointerup();

  expect(pressed).toBeTruthy();
  expect(firedTimes).toBe(1);
  expect(buttonPressed).toBe("q");
});

it('Keyboard buttonAttribute will work', () => {
  setDOM();

  new Keyboard({
    buttonAttributes: [
      {
        attribute: "aria-label",
        value: "bee",
        buttons: "b B"
      }
    ]
  });
});

it('Keyboard buttonAttribute will warn about invalid entries', () => {
  setDOM();

  new Keyboard({
    buttonAttributes: [
      {
        attribute: false,
        value: null
      }
    ]
  });
});

it('Keyboard recurseButtons will not work without a valid param', () => {
  setDOM();
  const keyboard = new Keyboard();
  expect(keyboard.recurseButtons()).toBe(false);
});

it('Keyboard will not work with a DOM element param without class', () => {
  try {
    const keyboardDOM = document.createElement("div");
    new Keyboard(keyboardDOM);
    expect(true).toBe(false);
  } catch (e) {
    expect(e.message).toBe("KEYBOARD_DOM_CLASS_ERROR");
  }
});

it('Keyboard will work with a DOM element param with class', () => {
  try {
    const keyboardClass = "my-keyboard";
    const keyboardDOM = document.createElement("div");
    keyboardDOM.className = keyboardClass;
    const keyboard = new Keyboard(keyboardDOM);

    expect(keyboard.keyboardDOMClass).toBe(keyboardClass);
  } catch (e) {
    expect(true).toBe(false);
  }
});

it('Keyboard handleKeyboardContainerMouseDown will work', () => {
  setDOM();

  const keyboard = new Keyboard();
  keyboard.keyboardDOM.onpointerdown();
});

it('Keyboard handleKeyboardContainerMouseDown will respect preventMouseDownDefault', () => {
  setDOM();

  let works = false;
  const keyboard = new Keyboard({ preventMouseDownDefault: true });
  keyboard.keyboardDOM.onpointerdown({ preventDefault: () => {
    works = true
  }});

  expect(works).toBe(true);
});

it('Keyboard handlePointerDown will work', () => {
  setDOM();

  const keyboard = new Keyboard();

  keyboard.setCaretPosition(3);
  expect(keyboard.getCaretPosition()).toBe(3);

  triggerDocumentPointerDown({
    target: document.body
  })

  expect(keyboard.getCaretPosition()).toBe(null);
});