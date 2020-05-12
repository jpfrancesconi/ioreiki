//components/index.js
import React, { Component } from 'react'; //Importamos react
import ReactDOM from 'react-dom';


class Root extends Component {
  render() {
    return (
      <div>
        {/* <a href="#" className="btn btn-primary">OBTENER ESTE CURSO</a> */}
        <button className="btn btn-primary">OBTENER ESTE CURSO</button>
      </div>
    )
  }
}

let container = document.getElementById('app');
let component = <Root />;

ReactDOM.render(component, container);
