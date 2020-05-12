import React, { Component } from 'react'; //Importamos react

function UserCard(props) {
  return (
    <h1>Usuario: {props.userName}</h1>
  );
}

export default UserCard;
